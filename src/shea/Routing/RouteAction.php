<?php


namespace Shea\Routing;


use Shea\Support\Str;

// 解析 uri 对应的方法，找到一个可用的方法
class RouteAction
{
    public static function parse($uri,$action)
    {
        if (is_null($action)) {
            return static::missingAction($uri);
        }

        // 如果是可回调的，并且不是数组直接将这个 action 赋值给 uses
        // 否则找到 action 的对应的操作
        if (is_callable($action,true)) {
            return ! is_array($action) ? ['uses' => $action] : [
                'uses' => $action[0].'@'.$action[1],
                'controller' => $action[0].'@'.$action[1],
            ];
        } elseif (!isset($action['uses'])) { // 如果 uses 没有设置,去 action 内找到一个可用的回调
            $action['uses'] = static::findCallable($action);
        }

        if (is_string($action['uses']) && !Str::contains($action['uses'],'@')) {
            $action['uses'] = static::makeInvokable($action['uses']);
        }

        return $action;
    }

    /**
     * 指定路由没有操作抛出异常
     * @param string $uri
     * @return array
     */
    protected static function missingAction($uri)
    {
        return ['uses' => function() use ($uri) {
            throw new \LogicException("Route for [{$uri}] has no action");
        }];
    }

    protected static function findCallable(array $action)
    {
        foreach ($action as $key => $value) {
            if (is_callable($value) && is_numeric($key)) {
                return $value;
            }
        }
    }

    protected static function makeInvokable($action)
    {
        if (!method_exists($action,'__invoke')) {
            throw new \UnexpectedValueException("Invalid route action: [{$action}]");
        }
        return $action.'@__invoke';
    }
}