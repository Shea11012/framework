<?php


use Shea\Support\HigherOrderTapProxy;

if (!function_exists('value')) {
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('tap')) {
    /**
     * @param $value
     * @param callable|null $callback
     * @return mixed
     */
    function tap($value,$callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);
        return $value;
    }
}