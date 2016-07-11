<?php

namespace App\Models\Tables;

use App\Models\Others\SortAndSearch\SortAndSearch;
use Illuminate\Database\Eloquent\Model;
use App\Models\Repository\ManagerInterface;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator;

class Manager extends Authenticatable implements ManagerInterface
{
    /**
     * @var string 定义数据表名称 Manager模型对应managers表，这里可以不写。
     */
    protected $table = 'managers';

    /**
     * @var array 允许操作的字段
     */
    protected $fillable = ['name', 'password', 'remark', 'email', 'status', 'phone', 'creator', 'updated_at', 'created_at'];

    /**
     * @var string 设置主键名称，默认是id。
     */
    protected $primaryKey = 'id';


    /**
     * @var array 数据隐藏的字段
     */
    protected $hidden = ['password'];

    /**
     * @var array 当AR对象被转换成数组或json后，可以通过该属性设置，追加一个字段到数组或json中去。
     */
    // protected $appends = ['is_admin'];

    /**
     * @var string 设置日期格式
     */
    protected $dateFormat = 'U';

    public $timestamps = true;

    public $dates = ['created_at', 'updated_at', 'last_login_at'];
    /**
     * @var array 显示在列表页的字段
     */
    protected $showInfo = ['id', 'name', 'email', 'status', 'last_login_at', 'created_at'];

    /**
     * 获取所有记录，并分页显示。
     *
     * @return mixed
     */
    public function getAll(SortAndSearch $sortAndSearch)
    {
        $model = null === $sortAndSearch->builder
            ? self::orderBy('created_at', 'desc')
            : $sortAndSearch->builder;

        return $model->paginate(2, $this->showInfo);
    }

    /**
     * 获取一条数据
     *
     * @param $id
     * @return mixed
     */
    public function getRow($id)
    {
        return static::findOrFail($id);
    }

    /**
     * 创建操作
     *
     * @param $data
     * @return static
     */
    public function insert($data)
    {
        $data['password'] = bcrypt($data['password']);
        $data['creator'] = \Auth::guard('back')->user()->name;

        return static::create($data);
    }

    /**
     * 修改操作
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function edit($id, $data)
    {
        $rowObj = static::getRow($id);

        return $rowObj->update($data);
    }

    /**
     * 删除操作，如果是超级管理员，不能删除
     *
     * @param $id
     * @return bool
     */
    public function remove($id)
    {
        $rowObj = static::getRow($id);

        if ($rowObj->is_super) {
            return true;
        }

        return $rowObj->delete();
    }

    /**
     * insert操作时，自动给creator自动赋值。$value表示当前操作的用户
     *
     * @param $value
     */
    public function setCreatorAttribute($value)
    {
        $this->attributes['creator'] = $value;
    }


    /**
     * select处理用户数据
     *
     * @param $value
     * @return string
     */
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }


}
