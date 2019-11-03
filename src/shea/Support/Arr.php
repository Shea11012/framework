<?php


namespace Shea\Support;


class Arr
{
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }
    public static function get($array,$key,$default = null)
    {
        if (! static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array,$key)) {
            return $array[$key];
        }

        if (strpos($key,'.') === false) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.',$key) as $segment) {
            if (static::accessible($array) && static::exists($array,$segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    public static function exists($array, $key)
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key,$array);
    }

    // 获取所有数组排除指定数组
    public static function except($array,$keys)
    {
        static::forget($array,$keys);

        return $array;
    }

    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            if (static::exists($array,$key)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.',$key);

            $array = &$original;

            while(count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * 转换数组为一个查询字符串
     * @param array $array
     * @return string
     */
    public static function query($array)
    {
        return http_build_query($array,null,'&',PHP_QUERY_RFC3986);
    }

    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

}