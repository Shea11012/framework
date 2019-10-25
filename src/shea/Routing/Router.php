<?php


namespace Shea\Routing;

use Shea\Container\Container;
use Shea\Contracts\Routing\Registrar as RegistrarContract;

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
        // todo 先去实现一个路由集合
        $this->container = $container ?: new Container();
    }
}