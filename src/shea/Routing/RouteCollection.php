<?php


namespace Shea\Routing;


class RouteCollection implements \Countable,\IteratorAggregate
{
    /**
     * 通过请求方法访问 route 数组
     * @var array
     */
    protected $routes = [];

    /**
     * 平铺 route 数组
     */
    protected $allRoutes = [];

    /**
     * 通过名字查找路由表
     * @var array
     */
    protected $nameList = [];

    /**
     * 通过控制器方法查找路由表
     * @var array
     */
    protected $actionList = [];

    /**
     * 添加一个 route 实例进集合
     * @param Route $route
     * @return Route
     */
    public function add(Route $route)
    {
        $this->addToCollections($route);
        $this->addLookups($route);
        return $route;
    }

    protected function addToCollections(Route $route)
    {
        // todo 实现 route 实例
        $domainAndUri = $route->getDomain().$route->uri();
    }
}