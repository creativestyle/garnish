<?php

namespace Creativestyle\Garnish\Utils;


class ArrayUtils
{
    /**
     * @param array $arr
     * @param array $keys
     * @return array
     */
    public static function pick(array $arr, array $keys)
    {
        return array_intersect_key($arr, array_combine($keys, $keys));
    }
}