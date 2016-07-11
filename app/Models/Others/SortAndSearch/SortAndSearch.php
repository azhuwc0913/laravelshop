<?php

namespace App\Models\Others\SortAndSearch;

use App\Helpers\StringHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 排序和搜索模型
 *
 * Class SortAndSearch
 * @package App\Models\Others\SortAndSearch
 */
class SortAndSearch extends Model
{
    use Sort, Search;

    /**
     * 排序的标识字段
     */
    const SORT_FIELD = 'SortField';

    /**
     * 正序的标识符，用于排序时的url参数前缀
     */
    const ASC_FLAG = '+';

    /**
     * 倒序的标识符，用于排序时的url参数前缀
     */
    const DESC_FLAG = '-';

    /**
     * 视图中的排序默认class样式，正序为sorting_asc，倒序为sorting_desc
     */
    const CLASS_NAME = 'sorting';

    /**
     * 搜索的标识字段
     */
    const SEARCH_FIELD = 'search';

    /**
     * like查询的样式
     */
    const LIKE_TYPE = '%x%';

    /**
     * between查询时，开始的后缀。
     */
    const BETWEEN_START_EXT = 'start';

    /**
     * between查询时，结束的后缀。
     */
    const BETWEEN_END_EXT = 'end';

    /**
     * @var string 排序字段名称,不带标示符的
     */
    public $sortField = null;

    /**
     * @var string 下一次使用的排序标识符
     */
    public $nextSortFlag = null;

    /**
     * @var string 下一次使用的排序字段，带标识符
     */
    public $nextSortField = null;

    /**
     * @var string 排序的方式，asc或desc。
     */
    public $sortType = null;

    /**
     * @var Builder 连贯操作时的builder对象
     */
    public $builder = null;

    /**
     * @var array 排序和搜索时传递的参数数组
     */
    public $requestParam = [];

    /**
     * @var array 搜索的方式
     */
    public $searchTypes;

    /**
     * @var Model 排序和搜索时使用的模型
     */
    public $model = null;

    public $format = null;

    /**
     * 搜索和查询的执行方法
     *
     * @param $requestParam
     * @param Model $model
     * @param array|null $searchTypes
     * @return $this
     */
    public function execute($requestParam, Model $model, array $searchTypes = null)
    {
        if (!empty($requestParam)) {
            $this->requestParam = $this->unsetEmptyValue($requestParam);
            $this->model = $model;

            if (isset($this->requestParam[static::SORT_FIELD])) {
                $this->sort();
            }

            if (!is_null($searchTypes) && isset($this->requestParam[static::SEARCH_FIELD])) {
                $searchTypes = $this->getFormat($searchTypes);
                $this->searchTypes = $searchTypes;
                $this->search();
            }
        }

        return $this;
    }

    /**
     * 执行排序操作
     *
     * @param $requestParam array 排序和搜索时的request参数
     * @param $model Model 排序和搜索使用的模型。
     * @return $this|bool 返回当前对象
     */
    protected function sort()
    {
        $field = $this->requestParam[static::SORT_FIELD];
        $this->sortType = $this->getSortTypeAndSetFlg($field);
        $this->sortField = $this->getSortFieldName($field);
        $this->nextSortField = $this->nextSortFlag . $this->sortField;
        $this->builder = $this->model->orderBy($this->sortField, $this->sortType);
    }

    /**
     * 获取当前排序类型，并在字段前设置标识符
     *
     * @param $field
     * @return bool|null|string
     */
    protected function getSortTypeAndSetFlg($field)
    {
        $type = null;
        $firstStr = StringHelper::subPos($field, 1);

        if (!$firstStr) {
            return false;
        }

        switch ($firstStr) {
            case static::ASC_FLAG :
                $this->nextSortFlag = static::DESC_FLAG;
                $type = 'asc';
                break;
            case static::DESC_FLAG :
                $this->nextSortFlag = static::ASC_FLAG;
                $type = 'desc';
                break;
            default :
                $this->nextSortFlag = static::ASC_FLAG;
                $type = 'desc';
                break;
        }

        return $type;
    }


    /**
     * 获取不含标识符的真实排序字段名称
     *
     * @param $field
     * @return mixed
     */
    protected function getSortFieldName($field)
    {
        $firstStr = StringHelper::subPos($field, 1);
        $strLen = strlen($field);

        if (static::ASC_FLAG === $firstStr || static::DESC_FLAG === $firstStr) {
            return StringHelper::subPos($field, -$strLen + 1);
        }

        return $field;
    }

    /**
     * 执行搜索操作
     */
    protected function search()
    {
        if (is_null($this->builder)) {
            $this->builder = $this->model;
        }

        $this->setSearchWhere();
    }


    /**
     * 设置搜索时的where条件，添加到Builder对象上。
     */
    protected function setSearchWhere()
    {
        $searchParams = $this->unsetNoSearchParam();
        $bool = false;
        $searchParams = $this->formatData($searchParams);

        foreach ($searchParams as $key => $item) {
            foreach ($this->searchTypes as $k => $v) {
                switch ($k) {
                    case 'like' :
                        $bool = in_array($key, $v);
                        break;
                    case 'between' :
                        $bool = in_array($key, $v['start']) || in_array($key, $v['end']);
                        break;
                    default:
                        break;
                }

                if ($bool) {
                    $method = 'set' . ucfirst($k);
                    $this->$method($key, $item);
                    $bool = false;
                    break;
                }
            }

        }

    }

    /**
     * 删除提交时的空参数
     *
     * @param $paramsArr
     * @return mixed
     */
    protected function unsetEmptyValue($paramsArr)
    {
        if (!empty($paramsArr)) {
            foreach ($paramsArr as $key => $item) {
                if (0 === strcmp('', $item)) {
                    unset($paramsArr[$key]);
                }
            }
        }

        return $paramsArr;
    }

    /**
     * 过滤非查询参数
     *
     * @return array
     */
    protected function unsetNoSearchParam()
    {
        $searchArr = array_flatten($this->searchTypes);
        $paramArr = $this->requestParam;

        foreach ($paramArr as $key => $item) {
            if (!in_array($key, $searchArr)) {
                unset($paramArr[$key]);
            }
        }

        return $paramArr;
    }

    /**
     * 格式化数据
     */
    protected function formatData($searchParams)
    {
        if (!is_null($this->format)) {
            $format = $this->format;

            if (!empty($searchParams)) {
                foreach ($searchParams as $key => $item) {
                    if (key_exists($key, $format)) {
                        $formatMethod = $format[$key];
                        $searchParams[$key] = $formatMethod($item);
                    }
                }
            }
        }

        return $searchParams;

    }

    protected function getFormat($searchTypes)
    {
        if (isset($searchTypes['format']) && !empty($searchTypes['format'])) {
            $this->format = $searchTypes['format'];
            unset($searchTypes['format']);
        }

        return $searchTypes;
    }


}
