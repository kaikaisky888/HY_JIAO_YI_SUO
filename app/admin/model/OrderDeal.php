<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-25 01:15:22
 * @LastEditTime: 2021-07-28 13:59:14
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class OrderDeal extends TimeModel
{

    protected $name = "order_deal";

    protected $deleteTime = "delete_time";

    
    public function productLists()
    {
        return $this->belongsTo('\app\admin\model\ProductLists', 'product_id', 'id');
    }

    public function memberUser()
    {
        return $this->belongsTo('\app\admin\model\MemberUser', 'uid', 'id');
    }

}