<?php


namespace Shea\Routing;

use Shea\Container\Container;
use Shea\Contracts\Routing\Registrar as RegistrarContract;
use Shea\Http\Request;

class Router implements RegistrarContract
{

    /**
     * ioc 容器实例
     * @var \Shea\Container\Container
     */
    protected $container;

    /**
     * 路由集合实例
     * @var \Shea\Routing\RouteCollection
     */
    protected $routes;

    /**
     * 当前分发路由实例
     * @var \Shea\Routing\Route|null
     */
    protected $current;

    /**
     * 当前正在被分发的请求
     * @var \Shea\Http\Request
     */
    protected $currentRequest;

    /**
     * 所有中间件的 keys
     * @var array
     */
    protected $middleware = [];

    /**
     * 所有中间件组
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * 全局可用的正则表达式
     * @var array
     */
    protected $patterns = [];

    /**
     * 路由组属性栈
     * @var array
     */
    protected $groupStack = [];

    /**
     * 支持的路由动作
     * @var array
     */
    public static $verbs = ['GET','HEAD','POST','PUT','PATCH','DELETE','OPTIONS'];

    /**
     * Router instance.
     * @param Container|null $container
     */
    public function __construct(Container $container = null)
    {
        $this->routes = new RouteCollection();
        $this->container = $container ?: new Container();
    }

    /**
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function get($uri, $action = null)
    {
        return $this->addRoute(['GET','HEAD'],$uri,$action);
    }

    /**
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function post($uri, $action = null)
    {
        return $this->addRoute('POST',$uri,$action);
    }

    /**
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function put($uri,$action = null)
    {
        return $this->addRoute('PUT',$uri,$action);
    }

    /**
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function patch($uri,$action = null)
    {
        return $this->addRoute('PATCH',$uri,$action);
    }

    /**
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function delete($uri,$action = null)
    {
        return $this->addRoute('DELETE',$uri,$action);
    }

    /**
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function options($uri,$action = null)
    {
        return $this->addRoute('OPTIONS',$uri,$action);
    }

    /**
     * @param $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function any($uri,$action = null)
    {
        return $this->addRoute(self::$verbs,$uri,$action);
    }

    public function redirect($uri,$destination,$status = 302)
    {
        // todo
        /*return $this->any($uri,'\Shea\Routing\RedirectController')
                ->defaults('destination',$destination)
                ->defaults('status',$status);*/
    }

    /**
     * 添加一个路由到相关的路由集合
     * @param array|string $methods
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return \Shea\Routing\Route
     */
    public function addRoute($methods,$uri,$action)
    {
        return $this->routes->add($this->createRoute($methods,$uri,$action));
    }

    /**
     * 创建一个新的路由实例
     * @param array|string $methods
     * @param string $uri
     * @param mixed $action
     * @return \Shea\Routing\Route
     */
    protected function createRoute($methods,$uri,$action)
    {
        // 如果 action 对应的是一个 controller ，则转换控制器方法
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $route = $this->newRoute(
            $methods,$this->prefix($uri),$action
        );

        // groupStack 不为空则将 groupStack 里的属性合并至已经创建好的路由中，并准备退出路由返回给调用者
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        // 添加 where 子句至路由
        $this->addWhereClausesToRoute($route);

        return $route;
    }

    /**
     * 判断这个操作是否对应一个控制器
     * @param array $action
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if (!$action instanceof \Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * 添加一个基础控制器路由方法至 action 数组
     * @param $action
     * @return array|string
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        if (!empty($this->groupStack)) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }

        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * 追加最后一个组命名空间到 uses 子句中
     * @param string $class
     * @return string
     */
    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($class,'\\') !== 0
                ? $group['namespace'].'\\'.$class : $class;
    }

    /**
     * 创建一个新的路由对象
     * @param array|string $methods
     * @param string $uri
     * @param mixed $action
     * @return \Shea\Routing\Route
     */
    protected function newRoute($methods,$uri,$action)
    {
        return (new Route($methods,$uri,$action))
                    ->setRouter($this)
                    ->setContainer($this->container);
    }

    /**
     * 给指定的 uri 加上最后一个前缀
     * @param string $uri
     * @return string
     */
    protected function prefix($uri)
    {
        return trim(trim($this->getLastGroupPrefix(),'/').'/'.trim($uri,'/'),'/') ?: '/';
    }

    public function getLastGroupPrefix()
    {
        if (!empty($this->groupStack)) {
            $last = end($this->groupStack);
            return $last['prefix'] ?? '';
        }

        return '';
    }


    public function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    // 合并 group 属性至 route
    protected function mergeGroupAttributesIntoRoute(Route $route)
    {
        $route->setAction($this->mergeWithLastGroup($route->getAction()));
    }

    public function mergeWithLastGroup($new)
    {
        return RouteGroup::merge($new,end($this->groupStack));
    }

    protected function addWhereClausesToRoute(Route $route)
    {
        $route->where(array_merge(
            $this->patterns,$route->getAction()['where'] ?? []
        ));

        return $route;
    }

    /**
     * 注册一个指定动作的路由
     * @param array|string $methods
     * @param string $uri
     * @param \Closure|array|string|callable|null $action
     * @return Route
     */
    public function match($methods,$uri,$action = null)
    {
        return $this->addRoute(array_map('strtoupper',(array)$methods),$uri,$action);
    }

    /**
     * 创建一个带有共享属性的路由组
     * @param array $attributes
     * @param \Closure|string $routes
     */
    public function group(array $attributes,$routes)
    {
        $this->updateGroupStack($attributes);

        $this->loadRoutes($routes);
    }

    /**
     * 更新分组栈的指定属性
     * @param array $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if (!empty($this->groupStack)) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }

        $this->groupStack[] = $attributes;
    }

    protected function loadRoutes(\Closure $routes)
    {
        if ($routes instanceof \Closure) {
            $routes($this);
        } else {
            (new RouteFileRegistrar($this))->register($routes);
        }
    }

    /**
     * 分发请求至 application
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        return $this->dispatchToRoute($request);
    }

    /**
     * 分发请求至路由并返回响应
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatchToRoute(Request $request)
    {
        return $this->runRoute($request,$this->findRoute($request));
    }

    /**
     * 匹配一个指定请求的路由
     * @param Request $request
     * @return \Shea\Routing\Route
     */
    protected function findRoute(Request $request)
    {
        $this->current = $route = $this->routes->match($request);

        $this->container->instance(Route::class,$route);

        return $route;
    }

}