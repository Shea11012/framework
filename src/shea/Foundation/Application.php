<?php


namespace Shea\Foundation;


use Shea\Container\Container;
use Shea\Contracts\Foundation\Application as ApplicationContract;
use Shea\Routing\RoutingServiceProvider;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Application extends Container implements ApplicationContract, HttpKernelInterface
{
    /**
     *  The framework version
     * @var string
     */
    const VERSION = '1.0';

    /**
     * 基础路径
     * @var string
     */
    protected $basePath;

    /**
     * 标记程序在运行之前是否已经初始化过
     * @var bool
     */
    protected $hasBeenBootstrapped = false;

    /**
     * 判断 app 是否启动
     * @var bool
     */
    protected $booted = false;

    /**
     * 所有注册过的服务提供者
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * 可以自定义应用路径
     * @var string
     */
    protected $appPath;

    /**
     * 可以自定义的环境路径
     * @var string
     */
    protected $environmentPath;

    /**
     * 启动时加载的环境文件
     * @var string
     */
    protected $environmentFile = '.env';

    /**
     * 应用的命名空间
     * @var string
     */
    protected $namespace;

    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * 返回 application 版本
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);
    }

    protected function registerBaseServiceProviders()
    {
        // 在这注册路由提供者
        $this->register(new RoutingServiceProvider($this));
    }

    public function register($provider, $force = false)
    {
        $registered = $this->getProvider($provider);

        if ($registered && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }

        $this->serviceProviders[] = $provider;

        // todo

        return $provider;
    }

    /**
     * 是否已经启动
     * @param $provider
     * @return bool
     */

    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);
        return array_values(array_filter($this->serviceProviders, function ($value) use ($name) {
                return $value instanceof $name;
            }, ARRAY_FILTER_USE_BOTH))[0] ?? null;
    }

    // 设置基础路径
    public function setBasePath($basePath)
    {
        // 删除字符
        $this->basePath = rtrim($basePath, '\/');
        $this->bindPathsInContainer();
        return $this;
    }

    protected function bindPathsInContainer()
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.bootstrap', $this->bootstrapPath());
    }

    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            // 实例化一个 bootstrapper，然后返回，每个 bootstrapper 都必须有一个 bootstrap 方法
            $this->make($bootstrapper)->bootstrap($this);
        }

    }

    public function path($path = '')
    {
        $appPath = $this->appPath ?: $this->basePath . DIRECTORY_SEPARATOR . 'app';

        return $appPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function basePath($path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function configPath($path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function publicPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'public';
    }

    public function bootstrapPath($path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

}