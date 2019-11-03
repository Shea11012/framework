<?php


namespace Shea\Support;


class Str
{
    /**
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function contains($haystack,$needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack,$needle) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function is($pattern,$value)
    {
        $patterns = Arr::wrap($pattern);

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern == $value) {
                return true;
            }

            $pattern = preg_quote($pattern,'#');

            // 将 "library/*" 转为 "library/.*"
            $pattern = str_replace('\*','.*',$pattern);

            // \z 整段匹配，u 默认目标字符串为 utf-8
            if (preg_match('#^'.$pattern.'\z#u',$value) === 1) {
                return true;
            }
        }

        return false;
    }
}