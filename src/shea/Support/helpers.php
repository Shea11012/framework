<?php


use Shea\Support\Arr;
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

if (!function_exists('data_get')) {
    function data_get($target,$key,$default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.',$key);

        while(! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if (!is_array($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item,$key);
                }
                // todo
                return in_array('*',$key) ? Arr::collapse($result) : $result;
            }
        }
    }
}