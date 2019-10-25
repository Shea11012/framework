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
}