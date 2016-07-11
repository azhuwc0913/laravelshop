<?php

/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/6/13
 * Time: 下午11:19
 */

namespace App\Helpers;

class ArrayHelper
{
    public static function isInArray($search, array $arr)
    {
        $bool = false;
        //echo $search;
        //var_dump($arr); exit;

        foreach ($arr as $item) {
            if (!is_array($item)) {
                $bool = in_array($search, $arr);
            } else {
                $bool = static::isInArray($search, $item);
            }
        }


        return $bool;

    }
}