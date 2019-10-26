<?php


namespace Shea\Routing;


use Shea\Container\Container;
use Shea\Http\Exceptions\HttpResponseException;
use Shea\Support\Arr;

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
     * 获取路由的键值对参数
     * @return mixed
     */
    public function parameters()
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        throw new \LogicException('Route is not bound');
    }

    /**
     * 获取路由的原始键值对参数
     */
    public function originalParameters()
    {
        if (isset($this->originalParameters)) {
            return $this->originalParameters;
        }

        throw new \LogicException('Route is not bound');
    }

    /**
     * 获取路由的所有参数名
     * @return array|null
     */
    public function parameterNames()
    {
        if (isset($this->parameterNames)) {
            return $this->parameterNames;
        }

        return $this->parameterNames = $this->compileParameterNames();
    }

    protected function compileParameterNames()
    {
        preg_match_all('/\{(.*?)}\/',$this->getDomain().$this->uri,$matches);

        return array_map(function ($m) {
            return trim($m,'?');
        },$matches[1]);
    }

    public function where($name,$expression = null)
    {
        foreach ($this->parseWhere($name,$expression) as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    public function parseWhere($name,$expression)
    {
        return is_array($name) ? $name : [$name => $expression];
    }

    /**
     * 响应路由的 http 动词
     * @return array
     */
    public function methods()
    {
        return $this->methods;
    }

    /**
     * 获取或设置路由的 domain
     * @param string|null $domain
     * @return $this|string|null
     */
    public function domain($domain = null)
    {
        if (is_null($domain)) {
            return $this->getDomain();
        }

        $this->action['domain'] = $domain;

        return $this;
    }

    /**
     * 获取路由的 domain
     */
    public function getDomain()
    {
        return isset($this->action['domain']) ?
            str_replace(['http://','https://'],'',$this->action['domain']) : null;
    }

    /**
     * 获取路由实例前缀
     */
    public function getPrefix()
    {
        return $this->action['prefix'] ?? null;
    }

    /**
     * 添加一个路由前缀
     * @param string $prefix
     * @return $this
     */
    public function prefix($prefix)
    {
        $uri = rtrim($prefix,'/').'/'.ltrim($this->uri,'/');
        $this->uri = trim($uri,'/');
        return $this;
    }

    /**
     * 获取路由的 uri
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * 设置响应路由的 uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * 获取路由实例的别名
     * @return mixed|null
     */
    public function getName()
    {
        return $this->action['as'] ?? null;
    }

    /**
     * 添加或改变路由别名
     * @param $name
     * @return Route
     */
    public function name($name)
    {
        $this->action['as'] = isset($this->action['as']) ? $this->action['as'].$name : $name;

        return $this;
    }

    /**
     * 设置处理路由的方法
     */
    public function uses($action)
    {
        $action = is_string($action) ? $this->addGroupNamespaceToStringUses($action) : $action;

        return $this->setAction(array_merge($this->action,$this->parseAction([
            'uses' => $action,
            'controller' => $action,
        ])));
    }

    /**
     * @param Router $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * @param Container $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * 获取整个数组或者指定的属性值
     * @param string|null $key
     * @return mixed
     */
    public function getAction($key = null)
    {
        return Arr::get($this->action,$key);
    }

    /**
     * 为指定路由设置 action 数组
     * @param array $action
     * @return $this
     */
    public function setAction(array $action)
    {
        $this->action = $action;
        return $this;
    }

    public function defaults($key,$value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }
}