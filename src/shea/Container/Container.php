<?php

namespace Shea\Container;

use Shea\Contracts\Container\BindingResolutionException;
use Shea\Contracts\Container\Container as ContainerContract;

class Container implements ContainerContract, \ArrayAccess
{
    /**
     * 容器对象实例
     * @var static
     */
    protected static $instance;

    /**
     * 容器的共享实例
     * the current globally available container
     * @var object[]
     */
    protected $instances = [];

    /**
     * 容器的绑定
     * the container's bindings
     * @var array[]
     */
    protected $bindings = [];

    /**
     * the registered type aliases
     * 注册容器别名
     * @var string[]
     */
    protected $aliases = [];

    // 存入传入的参数
    protected $with = [];

    /**
     * 获取当前容器实例
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * @param ContainerContract|null $container
     * @return ContainerContract|null
     */
    public static function setInstance(ContainerContract $container = null)
    {
        return static::$instance = $container;
    }


    public function get($id)
    {
        try {
            return $this->make($id);
        } catch (\Exception $e) {
            if ($this->has($id)) {
                throw $e;
            }

            throw new EntryNotFoundException($id);
        }
    }

    /**
     * 从容器中获得给定类型实例
     * get the given type from instances
     *
     * @param $abstract
     * @param array $parameters
     * @param bool $newInstance
     * @return mixed|object
     */
    public function make($abstract, $parameters = [])
    {
        // 获取 abstract 的别名，判断是否已经注册过
        $abstract = $this->getAlias($abstract);

        // 根据参数判断是否需要一个新的实例
        $newInstance = !empty($parameters);

        if (isset($this->instances[$abstract]) && !$newInstance ) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        // 获取 abstract 的实例
        $concrete = $this->getConcrete($abstract);

        if ($concrete === $abstract || $concrete instanceof \Closure) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }


        if (!$newInstance) {
            $this->instances[$abstract] = $object;
        }

        array_pop($this->with);

        return $object;
    }

    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    public function build($concrete)
    {
        // 如果是闭包直接调用闭包，传入自己和所需参数
        if ($concrete instanceof \Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.",0,$e);
        }

        // 检测类是否可实例化
        if (!$reflector->isInstantiable()) {
            throw new BindingResolutionException("Target [$concrete] is not instantiable");
        }

        // 获取构造函数
        $constructor = $reflector->getConstructor();

        // 如果是 null，直接返回这个实例
        if (is_null($constructor)) {
            return new $concrete;
        }

        // 获取参数依赖
        $dependencies = $constructor->getParameters();

        // 解析依赖参数，并返回
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // 获取类型提示类，如果为 null 则是一个原始数据，否则解析这个类
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);
                continue;
            }

            $results[] = is_null($dependency->getclass())
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);
        }

        return $results;
    }

    // 解析类获取类名
    public function resolveClass(\ReflectionParameter $parameter)
    {
        return $this->make($parameter->getClass()->name);
    }

    public function resolvePrimitive(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new BindingResolutionException("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
    }

    // 单例
    public function singleton($abstract,$concrete = null)
    {
        $this->bind($abstract,$concrete,true);
    }

    // 根据别名获取真实类名
    public function getAlias($abstract)
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * 绑定别名
     * @param $abstract
     * @param $alias
     */
    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new \LogicException("[{$abstract}] is aliased to itself");
        }
        $this->aliases[$alias] = $abstract;
    }

    /**
     * 绑定一个类实例到容器
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (! $concrete instanceof \Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete','shared');
    }

    protected function getClosure($abstract,$concrete)
    {
        return function (Container $container,$parameters = []) use ($abstract,$concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->make($concrete,$parameters);
        };
    }

    /**
     * @return array|mixed
     */
    public function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }

    protected function dropStaleInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    // 判断是否已经绑定过
    public function has($id)
    {
        return $this->bound($id);
    }

    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || isset($this->aliases[$abstract]);
    }

    public function factory($abstract)
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    // 当对一个对象使用 empty 或 isset 时生效
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    // 使用 empty 或像数组那样获取值时生效
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    // 对一个类像数组那样赋值时，生效
    public function offsetSet($key, $value)
    {
        $this->bind($key,$value instanceof \Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    // 使用 unset 时生效
    public function offsetUnset($key)
    {
        unset($this->bindings[$key],$this->instances[$key]);
    }

    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key,$value)
    {
        $this[$key] = $value;
    }

    protected function hasParameterOverride($dependency)
    {
        return array_key_exists($dependency->name,$this->getLastParameterOverride());
    }

    protected function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }
}