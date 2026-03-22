<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 00:09:27
 * @LastEditTime: 2021-09-01 17:25:17
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class GoodLists extends TimeModel
{

    protected $name = "good_lists";
    
    protected $deleteTime = "delete_time";

    public function getProductLists()
    {
        $pl= new \app\admin\model\ProductLists();
        $where[] = ['types','like','%4%'];
        $list = $pl->where('status',1)->where($where)->field('id,title')->select();
        return $list;
    }

    public function productLists()
    {
        return $this->belongsTo('\app\admin\model\ProductLists', 'product_id', 'id');
    }

}