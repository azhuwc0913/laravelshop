<?php

namespace App\Models\Others\SortAndSearch;


trait Sort
{
    /**
     * 设置视图中的排序class。
     *
     * @param $field
     * @return string
     */
    public function setSortClass($field)
    {
        $className = static::CLASS_NAME;

        if ($field !== $this->sortField) {
            return $className;
        }

        return $className . '_' . $this->sortType;
    }

    /**
     * 设置排序时的url和参数
     *
     * @param $routeName string 路由别名
     * @param $field string 排序的字段
     * @param array $otherParamsArr 排序时的参数
     * @return string 返回一个带参数的路径
     */
    public function setSortUrl($routeName, $field, array $otherParamsArr = [])
    {
        $sortKey = static::SORT_FIELD;
        $paramsArr = [];
        $otherParamsArr = array_merge($this->requestParam, $otherParamsArr);

        if ($field === $this->sortField) {
            $paramsArr = array_merge($otherParamsArr, [$sortKey => $this->nextSortField]);
        } else {
            $paramsArr = array_merge($otherParamsArr, [$sortKey => $field]);
        }

        return route($routeName, $paramsArr);
    }

}
