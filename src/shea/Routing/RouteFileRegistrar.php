<?php


namespace Shea\Routing;


class RouteFileRegistrar
{
    /**
     * @var \Shea\Routing\Router
     */
    protected $router;

    /**
     * RouteFileRegistrar constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * 引入指定的路由文件
     * @param $routes
     */
    public function register($routes)
    {
        $router = $this->router;
        require $routes;
    }
}