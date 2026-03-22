<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-25 01:15:22
 * @LastEditTime: 2021-08-08 18:15:16
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class OrderLeverdeal extends TimeModel
{

    protected $name = "order_leverdeal";

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