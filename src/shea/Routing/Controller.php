<?php


namespace Shea\Routing;


use http\Exception\BadMethodCallException;

abstract class Controller
{
    protected $middleware = [];

    public function middleware($middleware,array $options = [])
    {
        foreach ((array)$middleware as $m) {
            $this->middleware[] = [
                'middleware' => $m,
                'options' => &$options,
            ];
        }

        return new ControllerMiddlewareOptions($options);
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function callAction($method,$parameters)
    {
        return call_user_func_array([$this,$method],$parameters);
    }

    public function __call($method,$parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist',static::class,$method
        ));
    }
}