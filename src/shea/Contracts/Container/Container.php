<?php

namespace Shea\Contracts\Container;

use Psr\Container\ContainerInterface;

interface Container extends ContainerInterface
{
    /**
     * 检查 abstract 是否已经绑定过
     * determine if the given abstract type has been bound
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract);

    /**
     * 绑定一个 abstract 和 concrete 进入容器，shared 决定是否共享实例
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null,bool $shared = false);

    /**
     * 在容器内注册一个共享实例
     * Register an existing instance as shared in the container
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance($abstract,$instance);

    /**
     * get a closure to resolve the given type from the container
     * @param string $abstract
     * @return \Closure
     */
    public function factory(string $abstract);

    /**
     * 从容器中解析给定类型
     * resolve the given type from the container
     * @param $abstract
     * @param array $parameters
     * @return mixed
     */
    public function make($abstract,array $parameters = []);
}