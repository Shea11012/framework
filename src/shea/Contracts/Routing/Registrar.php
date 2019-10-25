<?php


namespace Shea\Contracts\Routing;


interface Registrar
{
    /**
     * 注册一个 get 路由
     * @param string $uri
     * @param \Closure|array|string|callable $action
     * @return \Shea\Routing\Route
     */
    public function get($uri,$action);

    /**
     * 注册一个 post 路由
     * @param string $uri
     * @param \Closure|array|string|callable $action
     * @return \Shea\Routing\Route
     */
    public function post($uri,$action);

    /**
     * 注册一个 put 路由
     * @param string $uri
     * @param \Closure|array|string|callable $action
     * @return \Shea\Routing\Route
     */
    public function put($uri,$action);

    /**
     * 注册一个 delete 路由
     * @param string $uri
     * @param \Closure|array|string|callable $action
     * @return \Shea\Routing\Route
     */
    public function delete($uri,$action);

    /**
     * 注册一个 patch 路由
     * @param string $uri
     * @param \Closure|array|string|callable $action
     * @return \Shea\Routing\Route
     */
    public function patch($uri,$action);

    /**
     * 注册一个 options 路由
     * @param string $uri
     * @param \Closure|array|string|callable $action
     * @return \Shea\Routing\Route
     */
    public function options($uri,$action);

    /**
     * 注册一个 match 路由根据给定的动作匹配
     * @param array|string $methods
     * @param string $uri
     * @param \Closure|array|string|callable $action
     * @return \Shea\Routing\Route
     */
    public function match($methods,$uri,$action);

    /**
     * 根据共享属性创建一个路由组
     * @param array $attributes
     * @param \Closure|string $routes
     * @return void
     */
    public function group(array $attributes,$routes);
}