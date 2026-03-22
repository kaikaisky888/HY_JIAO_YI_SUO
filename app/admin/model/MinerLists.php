<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 21:09:01
 * @LastEditTime: 2021-06-26 21:20:13
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class MinerLists extends TimeModel
{

    protected $name = "miner_lists";

    protected $deleteTime = "delete_time";

    public function getProductLists()
    {
        $pl= new \app\admin\model\ProductLists();
        $list = $pl->where('status',1)->field('id,title')->select();
        return $list;
    }
    
    public function productLists()
    {
        return $this->belongsTo('\app\admin\model\ProductLists', 'product_id', 'id');
    }

    

}