<?php


namespace Shea\Routing;


trait RouteDependencyResolverTrait
{
    /**
     * @param array $parameters
     * @param object $instance
     * @param string $method
     * @return array
     */
    protected function resolveClassMethodDependencies(array $parameters,$instance,$method)
    {
        if (!method_exists($instance,$method)) {
            return $parameters;
        }

        return $this->resolveMethodDependencies(
            $parameters,new \ReflectionMethod($instance,$method)
        );
    }

    /**
     * 解析给定方法依赖
     * @param array $parameters
     * @param \ReflectionFunctionAbstract $reflector
     * @return array
     * @throws \ReflectionException
     */
    public function resolveMethodDependencies(array $parameters,\ReflectionFunctionAbstract $reflector)
    {
        $instanceCount = 0;
        // 将参数key变为数值key
        $values = array_values($parameters);
        // 获取到指定方法的所有依赖参数
        foreach ($reflector->getParameters() as $key => $parameter) {
            // 转换依赖
            $instance = $this->transformDependency(
                $parameter,$parameters
            );

            if (!is_null($instance)) {
                $instanceCount++;
                $this->spliceIntoParameters($parameters,$key,$instance);
            } elseif (
                !isset($values[$key - $instanceCount]) // 判断指定的key的值是否设置
                &&
                $parameter->isDefaultValueAvailable()) {
                $this->spliceIntoParameters($parameters,$key,$parameter->getDefaultValue());
            }
        }

        return $parameters;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param $parameters
     * @return mixed
     * @throws \ReflectionException
     */
    protected function transformDependency(\ReflectionParameter $parameter, $parameters)
    {
        // 获取参数的类型提示类
        $class = $parameter->getClass();

        if ($class && !$this->alreadyInParameters($class->name,$parameters)) {
            return $parameter->isDefaultValueAvailable()
                ? $parameter->getDefaultValue()
                : $this->container->make($class->name);
        }
    }

    /**
     * 判断参数是否已经存在给定的参数内
     * @param string $class
     * @param array $parameters
     * @return bool
     */
    protected function alreadyInParameters($class,array $parameters)
    {
        foreach ($parameters as $parameter) {
            if ($parameter instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据 key 将实例插入
     * @param array $parameters
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    protected function spliceIntoParameters(array &$parameters,$offset,$value)
    {
        array_splice($parameters,$offset,0,[$value]);
    }
}