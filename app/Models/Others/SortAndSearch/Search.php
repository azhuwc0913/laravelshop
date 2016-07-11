<?php

namespace App\Models\Others\SortAndSearch;


use App\Helpers\StringHelper;

trait Search
{
    /**
     * 设置like查询条件
     *
     * @param $key
     * @param $val
     */
    public function setLike($key, $val)
    {
        $val = str_replace('x', $val, static::LIKE_TYPE);
        $this->builder = $this->builder->where($key, 'like', $val);
    }

    /**
     * 设置between查询条件
     *
     * @param $key
     * @param $val
     */
    public function setBetween($key, $val)
    {
        $type = StringHelper::subPosByTag($key, '_', 'last');
        $key = $type[1];

        if (0 === strcmp(static::BETWEEN_START_EXT, $type[0])) {
            $this->builder = $this->builder->where($key, '>=', $val);
        } else {
            $this->builder = $this->builder->where($key, '<=', $val);
        }
    }
}
