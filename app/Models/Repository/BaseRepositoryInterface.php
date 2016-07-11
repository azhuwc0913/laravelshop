<?php
/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/6/5
 * Time: 下午9:01
 */

namespace App\Models\Repository;


use App\Models\Others\SortAndSearch\SortAndSearch;

interface BaseRepositoryInterface
{
    public function insert($data);

    public function edit($id, $data);

    public function remove($id);

    public function getAll(SortAndSearch $sortAndSearch);

    public function getRow($id);
}