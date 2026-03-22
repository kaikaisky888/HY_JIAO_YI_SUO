<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 22:41:00
 * @LastEditTime: 2021-06-30 13:29:02
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class IeoLists extends TimeModel
{

    protected $name = "ieo_lists";

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