<?php

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    //
	protected $table = 'comment';
	protected $fillable = ['member_id', 'goods_id', 'content', 'addtime', 'star'];
	public $timestamps = false;

    public function add_impression($imp_data, $goods_id){
		//将提交过来的印象的中文,号换成英文,号
		$imp_data = str_replace('，', ',', $imp_data);
		$imp_data = explode(',', $imp_data);
		foreach($imp_data as $k=>$v){
         //判断这个印象是否出现过
			$has = DB::table('impression')
				->where('imp_name', '=', $v)
				->where('goods_id', '=', $goods_id)
				->lists('imp_name');
			if($has){
				DB::table('impression')
					->where('imp_name', '=', $v)
					->where('goods_id', '=', $goods_id)
					->increment('imp_count', 1);
			}else{
				DB::table('impression')
					->insert([
						'imp_name' => $v,
						'goods_id' => $goods_id,
					]);
			}
		}
	}

	public function search($goods_id, $page=1, $pageSize = 3){
    //先取出该商品的所有的品论,然后自定义分页
		$total = Comment::where('goods_id', $goods_id)->get();

		$page_count = ceil(count($total)/$pageSize);

		$current_page = max(1, $page);



			$star_data = DB::table('comment')
					->where('goods_id', '=', $goods_id)
					->lists('star');

			$level_count = count($star_data);

			$best = $normal = $bad = 0;

			foreach($star_data as $k=>$v){
				if($v==3){
                   $normal++;
				}elseif($v>3){
					$best++;
				}else{
					$bad++;
				}
			}



			$best_rate = round(($best/$level_count)*100, 2);

			$normal_rate = round(($normal/$level_count)*100, 2);

			$bad_rate = round(($bad/$level_count)*100, 2);

			//取出所有的印象数据
			$imp_data = DB::table('impression')
					->where('goods_id', '=', $goods_id)
					->select('id', 'imp_name', 'imp_count')
					->get();


		$offset = ($page-1)*$pageSize;

		$data = DB::table('comment as a')
				->leftJoin('member as b', 'b.id', '=', 'a.member_id')
				->leftJoin('reply as c', 'c.comment_id', '=', 'a.id')
				->where('a.goods_id', '=', $goods_id)
				->select('a.goods_id', 'a.content','a.star','a.is_used','a.addtime', 'b.email', DB::raw('count(*) as count'))
				->groupBy('a.id')
				->skip($offset)->take($pageSize)
				->get();

		return array(
			'data'       =>  $data,
	  'page_count'       =>  $page_count,
	  'best_rate'        =>  $best_rate,
	  'normal_rate'      =>  $normal_rate,
	  'bad_rate'         =>  $bad_rate,
	  'imp_data'         =>  $imp_data
		);
	}
}
