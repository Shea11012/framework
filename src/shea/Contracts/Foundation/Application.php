<?php

namespace Shea\Contracts\Foundation;

use Shea\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * 获取版本号
     * get the version number of the application
     * @return string
     */
    public function version(): string;

    /**
     * 获取框架的安装路径
     * get the base path of the basePath
     * @return string
     */
    public function basePath(): string;

    /**
     * 在应用中注册服务提供者
     * register a service provider with the application
     * @param $provider
     * @param bool $force
     * @return mixed
     */
    public function register($provider,bool $force = false);
}