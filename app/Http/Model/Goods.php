<?php

namespace App\Http\Model;

use App\Models\Others\SortAndSearch\SortAndSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Goods extends Model
{
    //
	protected $table = 'goods';
	public $timestamps = false;
	protected $fillable = [
		'goods_name',
		'shop_price',
		'market_price',
		'logo',
		'sm_logo',
		'goods_desc',
		'is_on_sale',
		'is_new',
		'is_best',
		'is_hot',
		'cat_id',
		'is_promote',
		'promote_price',
		'promote_start_time',
		'promote_end_time',
		'addtime',
		'sort_num',
		'type_id'
	];

	//显示在列表页中的column
	protected $showInfo = ['id', 'goods_name', 'shop_price', 'is_on_sale', 'is_best', 'is_new', 'is_new', 'sort_num'];

	public function getAll(SortAndSearch $sortAndSearch)
	{
		$model = null === $sortAndSearch->builder
				? self::orderBy('addtime', 'desc')
				: $sortAndSearch->builder;

		return $model->paginate(2, $this->showInfo);
	}
	/**
	 * @param $goods_id
	 * @param $member_price
	 */
	public function add_member_price($goods_id, $member_price){
		foreach ($member_price as $k => $v) {
			DB::table('member_price')->insert(
				['goods_id' => $goods_id, 'level_id'=>$k+1, 'price'=>$v]
			);
		}
	}


	/**
	 * @param $goods_id
	 * @param $gas
	 * @param $prices
	 */
	public function add_goods_attr($goods_id, $gas, $prices){
		foreach ($gas as $k => $v) {
			  	foreach ($v as $k1=>$v1) {
					if(empty($v1))
						continue;
					$price = isset($prices[$k][$k1])?$prices[$k][$k1]:'';

						DB::table('goods_attr')->insert([
							'goods_id'   => $goods_id,
							'attr_id'    => $k,
							'attr_value' => $v1,
							'attr_price' => $price
						]);
				}
		}
	}

	/**
	 * @param $goods_id
	 * @param $data
	 */
	public function add_goods_pics($goods_id, $data){
         DB::table('goods_pics')->insert([
			 'goods_id' => $goods_id,
			 'pic'      => $data['images'][0],
			 'sm_pic'   => $data['images'][1]
		 ]);
	}

	/**
	 * @param $goods_id
	 *
	 * @return mixed
	 *
	 * 获取商品对应的会员和会员价格信息
	 */
	public function select_member_data($goods_id){
		return  $data = DB::table('member_level as a')
				->leftJoin('member_price as b', 'a.id','=','b.level_id')
				->select('a.*', 'b.price')
				->orderBy('a.id', 'asc')
				->where('b.goods_id', '=', $goods_id)
				->get();
	}

	/**
	 * @param $goods_id
	 *
	 * @return mixed
	 *
	 * 获取商品对应的图片
	 */
	public function get_goods_pics($goods_id){
		return $data = DB::table('goods_pics')
				->where('goods_id', '=', $goods_id)
				->get();
	}

	/**
	 * @param $goods_id
	 *
	 * @return mixed
	 *
	 * 获取商品对应的属性值
	 */
	public function get_goods_attributes($goods_id){
		return $data = DB::table('goods_attr as a')
				->leftJoin('attribute as b', 'b.id', '=', 'a.attr_id')
				->where('a.goods_id', '=', $goods_id)
				->orderBy('a.attr_id', 'asc')
				->select('a.attr_id as id','a.attr_value','a.attr_price','b.attr_name','b.attr_value as value','b.attr_type','a.id as attr_id')
				->get();
	}

	/**
	 * @param $goods_id
	 *
	 * @return mixed
	 */
	public function get_goods_unique_attrs($goods_id){
		return $data = DB::table('goods_attr as a')
				->leftJoin('attribute as b', 'b.id', '=', 'a.attr_id')
				->where('a.goods_id', '=', $goods_id)
				->where('b.attr_type', '=', 0)
				->orderBy('a.attr_id', 'asc')
				->select('a.attr_id as id', 'a.attr_value', 'a.attr_price','b.attr_name','b.attr_type','a.id as attr_id')
				->get();
	}

	/**
	 * @param $goods_id
	 *
	 * @return mixed
	 */
	public function get_goods_option_attrs($goods_id){
		return $data = DB::table('goods_attr as a')
				->leftJoin('attribute as b', 'b.id', '=', 'a.attr_id')
				->where('a.goods_id', '=', $goods_id)
				->where('b.attr_type', '=', 1)
				->orderBy('a.attr_id', 'asc')
				->select('a.attr_id as id','a.attr_value','a.attr_price','b.attr_name','b.attr_value as value','b.attr_type','a.id as attr_id')
				->get();
	}

	/**
	 * @param $goodsAttrs
	 * @param $goods
	 *
	 * @return mixed
	 */
	public function get_other_attributes($goodsAttrs,$goods){
		$arr = array();

		foreach ($goodsAttrs as $v) {
			$arr[] = $v->id;
		}

		$arr = array_unique($arr);


		//取出这种商品类型对应的所有属性id

		return $data = DB::table('attribute as a')
				->select('a.id','a.attr_value as value','a.attr_name','a.attr_type','b.attr_value','b.attr_price','b.id as attr_id')
				->leftJoin('goods_attr as b', 'a.id', '=', 'b.attr_id')
				->whereNotIn('a.id', $arr)
				->Where('type_id', '=', $goods->type_id)
				->get();
	}

	/**
	 * @param $goods_id
	 */
	public function delete_goods_image($goods_id){
		$images = DB::table('goods')
				->where('id', '=', $goods_id)
				->select('logo', 'sm_logo')
				->get();
		foreach ($images as $image) {
			deleteImage($image->logo);
			deleteImage($image->sm_logo);
		}
	}

	/**
	 * @param $data
	 * @param  Goods $goods
	 */
	public function update_member_price($data, $goods){
		//先删除数据库中原有的会员价格
		DB::table('member_price')->where('goods_id', '=', $goods->id)->delete();
		foreach ($data as $k => $v) {
			if(empty($v))
				continue ;
			DB::table('member_price')
					->where('goods_id', '=', $goods->id)
					->insert([
						    'goods_id'     => $goods->id,
							'level_id'     => $k,
							'price'        => $v
					]);
		}
	}

	/**
	 * @param $data
	 * @param $price
	 * @param $goods
	 */
	public function update_old_ga($data, $price, $goods){

		//插入提交数据
		foreach ($data as $k => $v) {
			foreach ($v as $k1 => $v1) {
				if(empty($v1)){
					continue;
				}
				DB::table('goods_attr')
						->where('id', $k1)
						->update([
                        'attr_value' => $v1,
						'attr_price' => isset($price[$k][$k1])?$price[$k][$k1]:'0.00'
						]);
			}

		}
	}

	public function get_recycle_goods(){
		return $data = DB::table('goods')
				->where('is_delete', '=', 1)
				->get();
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getGoodsNameById($id){
		return $data = DB::table('goods')
				->where('id', '=', $id)
				->select('goods_name')
				->get();

	}

	/**
	 * @return mixed
	 */
	public function get_is_delete_goods(){
      return $data = DB::table('goods')
			  ->where('is_delete', '=', 1)
			  ->paginate(2);
	}

	/**
	 * @param $goods
	 */
	public function delete_member_price($goods){
		DB::table('member_price')
		->where('goods_id', '=', $goods->id)
		->delete();
	}

	/**
	 * @param $goods
	 */
	public function delete_goods_attr($goods){
		DB::table('goods_attr')
				->where('goods_id', '=', $goods->id)
				->delete();
	}

	/**
	 * @param $goods
	 */
	public function delete_goods_pics($goods){
		//取出对应的图片地址,从硬盘中删掉
		$images = DB::table('goods_pics')
				->where('goods_id', '=', $goods->id)
				->select('pic', 'sm_pic')
				->get();
		foreach($images as $image){
			deleteImage($image->pic);

			deleteImage($image->sm_pic);
		}
		DB::table('goods_pics')
				->where('goods_id', '=', $goods->id)
				->delete();
	}

	/**
	 * @param $goods
	 */
	public function delete_goods_store($goods){
		DB::table('goods_number')
				->where('goods_id', '=', $goods->id)
				->delete();
	}

	/**
	 * @return mixed
	 */
	public function get_crazy_goods(){
		return $data = DB::table('goods')
				->where('is_promote', '=', '1')
				->where('is_on_sale', '=', '1')
				->where('is_delete', '=', '0')
				->take(5)
				->get();
	}

	/**
	 * @return mixed
	 */
	public function get_hot_goods(){
		return $data = DB::table('goods')
				->where('is_hot', '=', '1')
				->where('is_on_sale', '=', '1')
				->where('is_delete', '=', '0')
				->take(5)
				->get();
	}

	/**
	 * @return mixed
	 */
	public function get_new_goods(){
		return $data = DB::table('goods')
				->where('is_new', '=', '1')
				->where('is_on_sale', '=', '1')
				->where('is_delete', '=', '0')
				->take(5)
				->get();
	}

	/**
	 * @return mixed
	 */
	public function get_best_goods(){
		return $data = DB::table('goods')
				->where('is_best', '=', '1')
				->where('is_on_sale', '=', '1')
				->where('is_delete', '=', '0')
				->take(5)
				->get();
	}

	public function get_goods_info($goods_id){
		//取出基本信息
		return $data= DB::table('goods')
				->where('id', '=', $goods_id)
				->first();
	}

	public function get_all_member_price($goods_id, $goods_price){
		 $data = DB::table('member_price as a')
				->leftJoin('member_level as b', 'b.id', '=', 'a.level_id')
				->where('goods_id', '=', $goods_id)
				->select('a.price', 'b.level_name', 'b.rate')
				->get();

		//如果对应的商品price是-1的话,表示按照折扣率进行计算对应的会员价格
		foreach ($data as $item) {
			if($item->price=='-1'){
				$item->price = ($item->rate/100)*$goods_price;
			}
		}
		return $data;
	}

	public function key_search($key){
		return $data = DB::table('goods as a')
				->leftJoin('goods_attr as b', 'b.goods_id', '=', 'a.id')
				->where('a.is_on_sale', '=', 1)
				->where('a.goods_name', 'like', "%$key%")
				->orWhere('a.goods_desc', 'like', "%$key%")
				->orWhere('b.attr_value', 'like', "%$key%")
				->distinct()
				->select(DB::raw('GROUP_CONCAT(php32_a.id) ids'))
				->get();

	}

	public function seach_goods_by_condition($attrGoodsId='', $price='', $order_by, $order_way, $payed_order){
		if($attrGoodsId && $price){
			$GoodsData = DB::table('goods as a')
					->whereBetween('a.shop_price', $price)
					->whereIn('c.order_id', $payed_order)
					->whereIn('a.id', $attrGoodsId)
					->leftJoin(DB::raw('(select count(*) as num,goods_id from php32_comment GROUP BY php32_comment.goods_id) as php32_b'), 'b.goods_id', '=', 'a.id')
					->leftJoin('order_goods as c', 'c.goods_id', '=', 'a.id')
					->select('a.shop_price', 'a.addtime', 'a.sm_logo', 'a.goods_name', DB::raw('IFNULL(php32_b.num,1) as number'), DB::raw('IFNULL(SUM(php32_c.goods_number),0) xl'))
					->groupBy('c.goods_id')
					->orderBy($order_by, $order_way)
					->paginate(10);
		}elseif($attrGoodsId){

			return $GoodsData = DB::table('goods as a')
					->leftJoin(DB::raw('(select count(*) as num,goods_id from php32_comment GROUP BY php32_comment.goods_id) as php32_b'), 'b.goods_id', '=', 'a.id')
					->leftJoin('order_goods as c', 'c.goods_id', '=', 'a.id')
					->whereIn('a.id', $attrGoodsId)
					->whereIn('c.order_id', $payed_order)
					->select('a.shop_price','a.addtime', 'a.sm_logo', 'a.goods_name', DB::raw('IFNULL(php32_b.num,1) as number'), DB::raw('IFNULL(SUM(php32_c.goods_number),0) xl'))
					->groupBy('c.goods_id')
					->orderBy($order_by, $order_way)
					->paginate(2);

		}elseif($price){
			return $GoodsData = DB::table('goods as a')
					->whereBetween('a.shop_price', $price)
					->leftJoin(DB::raw('(select count(*) as num,goods_id from php32_comment GROUP BY php32_comment.goods_id) as php32_b'), 'b.goods_id', '=', 'a.id')
					->select('a.shop_price','a.addtime', 'a.sm_logo', 'a.goods_name', DB::raw('IFNULL(php32_b.num,1) as number'))
					->paginate(2);
		}elseif(!is_null($attrGoodsId)&& count($attrGoodsId)<1){

			return $GoodsData= [];
		}else{

			return $GoodsData = DB::table('goods as a')
					->leftJoin(DB::raw('(select count(*) as num,goods_id from php32_comment GROUP BY php32_comment.goods_id) as php32_b'), 'b.goods_id', '=', 'a.id')
					->leftJoin('order_goods as c', 'c.goods_id', '=', 'a.id')
					//->whereIn('c.order_id', $payed_order)
					->select('a.shop_price', 'a.addtime', 'a.sm_logo', 'a.goods_name', DB::raw('IFNULL(php32_b.num,1) as number'), DB::raw('IFNULL(SUM(php32_c.goods_number),0) xl'))
					->groupBy('a.id')
					->orderBy($order_by, $order_way)
					->paginate(2);



		}
	}


	//后台商品搜索
	/**
	 * @param array $where
	 *
	 * @return mixed
	 */
//	public function search($where = array()){
//
//		$goods = new Goods();
//		if(isset($where['gn'])){
//		$goods = $goods->where('goods_name', 'like', $where['gn'].'%');
//		}
//
//		if(isset($where['cate_id'])){
//		$goods = $goods->where('cat_id', '=', $where['cate_id']);
//		}
//
//		return $goods->paginate(2);
//	}

}
