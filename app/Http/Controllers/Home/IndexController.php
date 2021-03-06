<?php

namespace App\Http\Controllers\Home;

use App\Http\Model\Category;
use App\Http\Model\Goods;
use Illuminate\Http\Request;
use App\Http\Controllers\Home\HomeController;
use App\Http\Requests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;



class IndexController extends HomeController
{

	/**
	 * @return $this
	 */
	public function index(Request $request){
			//设置页面信息
			$data = $this->set_page_info('京西商城','京西','京西','1',['index'],['index']);


			$navData = (new Category)->handle_category_data();


			//取出疯狂抢购的商品
			$crazyData = (new Goods())->get_crazy_goods();

			//取出热卖商品
			$hotData = (new Goods())->get_hot_goods();

			//取出新品上架
			$newData = (new Goods())->get_new_goods();

			//取出推荐商品
			$bestData = (new Goods())->get_best_goods();



			return view('home.index.index')->with([
					'data' => $data,
					'navData' => $navData,
					'crazyData' => $crazyData,
					'hotData' => $hotData,
					'newData' => $newData,
					'bestData' => $bestData,
			]);


           // return Cache::get('home_static_page');

	}

	public function showGoods($id, Goods $goods){


			//取出侧面导航的分类信息
			$navData = (new Category)->handle_category_data();

			//根据传过来的商品id取数据
			$goods_info = (new Goods())->get_goods_info($id);

			//取出商品图片便于展示
			$goods_pics = (new Goods())->get_goods_pics($id);

			//取出所有的会员的价格
			$mpData = $goods->get_all_member_price($id, $goods_info->shop_price);

		    //从商品属性表中取出这件商品所拥有的唯一属性
		   $goods_unique_attrs = $goods->get_goods_unique_attrs($id);

			//从商品属性表中取出这件商品所拥有的多选属性
			$goods_attrs = (new Goods())->get_goods_option_attrs($id);
			//将数据处理成三维数组
			$attrs = [];

			foreach ($goods_attrs as $k => $v) {
				$attrs[$v->id][] = $v;
			}

			$data = $this->set_page_info('商品详细页', '京西', '京西', '0', ['goods', 'common', 'jqzoom'], ['goods', 'jqzoom-core']);

			return view('home.index.goods')->with([
					'goods_info'           => $goods_info,
					'data'                 => $data,
					'goods_pics'           => $goods_pics,
					'navData'              => $navData,
					'attrs'                => $attrs,
					'mpData'               => $mpData,
					'goods_unique_attrs'   => $goods_unique_attrs
			])->render();

	}

	public function displayHistory(Request $request){
		$goods_id = $request->goods_id;

		$recentDisplay = isset($_COOKIE['display_history'])?unserialize($_COOKIE['display_history']):[];

		//将传过来的商品id存到$recentDisplay数组中

		array_unshift($recentDisplay, $goods_id);

		//去重复
		$recentDisplay = array_unique($recentDisplay);

		//最多取5条
		if(count($recentDisplay)>5){
			array_slice($recentDisplay, 0, 5);
		}

		//取出在cookie数组中的商品信息
        $ids = implode(',', $recentDisplay);
		$data = DB::table('goods')
				->wherein('id', $recentDisplay)
				->select('sm_logo', 'id', 'goods_name')
				->get();

		//将$recentDisplay存到cookie中

		setcookie('display_history', serialize($recentDisplay), time()+3600, '/');

		echo \json_encode($data);
	}

	public function listGoods(Request $request,$id){

		$get = $request->all();

		$price = $request->price;
		if(!empty($price)){
			$price = explode('-',$price);
		}

		list($keys, $values) = array_divide($get);

		$attrGoodsId = null;
		foreach($keys as $k=>$v){
			if(strpos($v,'attr_')!==false){
				//将$key分开,得到属性id
				$attrId = str_replace('attr_','',$v);
				//取出属性值中的最后一个'-'号
				$attr_name = strrchr($values[$k],'-');

				$attr_value = str_replace($attr_name,'', $values[$k]);

				//根据属性id和属性值取出对应的商品id,1,2,3,4的格式
				$goodsIds = DB::table('goods_attr')
						->where('attr_id', '=', $attrId)
						->where('attr_value', '=', $attr_value)
						->select(DB::raw('GROUP_CONCAT(goods_id) gids'))
						->get();


				if($goodsIds[0]->gids){
					//这个属性有商品
					$gids['gids'] = explode(',',$goodsIds[0]->gids);

					if($attrGoodsId === null){
						//说明是第一个搜索的属性
						//将第一次搜索到的商品id暂存到$attrGoodsId中
						$attrGoodsId = $gids['gids'];

					}else{
						//证明不是第一次搜索的商品id,和第一次搜索的属性商品id取交集,如果交集为空
						//后面的也就不用再取了

						$attrGoodsId = array_intersect($attrGoodsId,$gids['gids']);

						if(empty($attrGoodsId)){
							break;
						}
					}
				}else{
					//表明这个属性没有相应的商品
					//先将前几次的id交集清空
					$attrGoodsId = [];

					break;
				}


			}
		}

		//执行到这,表明前几次的属性商品是有共同的商品id
		////取出已经支付的订单id
		$payed_order = DB::table('order')->where('pay_status','=','是')	->select(DB::raw('GROUP_CONCAT(id) ids'))->get();

		$payed_order = explode(',', $payed_order[0]->ids);


		//默认的排序名称
		$order_by = 'xl';
		//默认的排序方式
		$order_way = 'desc';

		if(isset($get['orderby'])) {

			$od_by = $get['orderby'];

			if ($od_by) {
				if ($od_by == 'addtime') {
					$order_by = 'a.addtime';
				} elseif (strpos($od_by, 'price_') !== false) {
					$order_by = 'a.shop_price';
					if ($od_by == 'price_asc') {

						$order_way = 'asc';

					}
				}
			}
		}

		//在商品模型中封装根据属性id和查询价格获取数据的方法
		$GoodsData = (new Goods())->seach_goods_by_condition($attrGoodsId, $price, $order_by, $order_way, $payed_order);


		$value = [];

		foreach ($keys as $key => $v) {
			$val = explode('-', $values[$key]);
			$value[]=$val;
		}

		$searchData = (new Category())->getSearchConditionByCatId($id);

		//取出侧面导航的分类信息
		$navData = (new Category)->handle_category_data();



		$data = $this->set_page_info('商品搜索页', '京西', '京西', '0', ['list'], ['list']);

		return view('home.search.search')->with(['data'=>$data, 'navData'=>$navData, 'searchData'=>$searchData, 'get_keys'=>$keys, 'get_values'=>$value, 'GoodsData'=>$GoodsData, 'order_way'=>$order_way, 'order_by'=>$order_by]);
	}
}
