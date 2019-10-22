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
     * @param string $path
     * @return string
     */
    public function basePath($path = ''): string;

    /**
     * 获取 bootstrap 路径
     * @param string $path
     * @return string
     */
    public function bootstrapPath($path = ''): string;

    /**
     * 获取配置文件路径
     * @param string $path
     * @return string
     */
    public function configPath($path = ''): string;

    /**
     * 获取环境变量路径
     * @return string
     */
    public function environmentPath(): string;


    /**
     * 获取或检查当前环境变量
     * @param string|array ...$environment
     * @return string|array
     */
    public function environment(...$environment);

    /**
     * 注册所有配置文件提供者
     * @return void
     */
    public function registerConfiguredProviders();

    /**
     * 在应用中注册服务提供者
     * register a service provider with the application
     * @param $provider
     * @param bool $force
     * @return mixed
     */
    public function register($provider, bool $force = false);

    /**
     * 启动 application 的服务提供者
     * @return void
     */
    public function boot();
}