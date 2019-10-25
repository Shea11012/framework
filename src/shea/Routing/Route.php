<?php


namespace Shea\Routing;


use Shea\Container\Container;
use Shea\Http\Exceptions\HttpResponseException;

class Route
{
    use RouteDependencyResolverTrait;

    /**
     * 路由响应的 uri 模式
     * @var string
     */
    public $uri;

    /**
     * 路由响应的 http 方法
     * @var array
     */
    public $methods;

    /**
     * 路由操作数组
     * @var array
     */
    public $action;

    /**
     * 控制器实例
     * @var mixed
     */
    public $controller;

    /**
     * 路由的默认值
     * @var array
     */
    public $defaults = [];

    /**
     * 必要的正则表达式
     * @var array
     */
    public $wheres = [];

    /**
     * 匹配到的参数数组
     * @var
     */
    public $parameters;

    /**
     * 路由的参数名
     * @var array|null
     */
    public $parameterNames;

    /**
     * 匹配到的参数原始值
     * @var array
     */
    protected $originalParameters;

    /**
     * router 实例
     * @var \Shea\Routing\Router
     */
    protected $router;

    /**
     * container 实例
     * @var \Shea\Container\Container
     */
    protected $container;

    public function __construct($methods,$uri,$action)
    {
        $this->uri = $uri;
        $this->methods = (array)$methods;
        $this->action = $this->parseAction($action);
    }

    /**
     * 解析路由方法至 methods 内
     * @param callable|array|null $action
     * @return array
     */
    protected function parseAction($action)
    {
        return RouteAction::parse($this->uri,$action);
    }

    /**
     * 运行对应的方法并返回响应
     */
    public function run()
    {
        $this->container = $this->container ?: new Container();

        try {
            if ($this->isControllerAction()) {
                return $this->runController();
            }

            return $this->runCallable();
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    protected function runCallable()
    {
        $callable = $this->action['uses'];

        return $callable(...array_values($this->resolveMethodDependencies(
            $this->parametersWithoutNulls(),new \ReflectionFunction($this->action['uses'])
        )));
    }

    /**
     * 去掉为 null 的参数
     * @return array
     */
    public function parametersWithoutNulls()
    {
        return array_filter($this->parameters(),function ($p) {
            return !is_null($p);
        });
    }

    /**
     * @return mixed
     */
    public function parameters()
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        throw new \LogicException('Route is not bound');
    }
}