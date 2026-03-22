<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-25 01:15:22
 * @LastEditTime: 2021-08-05 21:18:43
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class OrderWiner extends TimeModel
{

    protected $name = "order_winer";

    protected $deleteTime = "delete_time";

    public function productLists()
    {
        return $this->belongsTo('\app\admin\model\ProductLists', 'product_id', 'id');
    }
    
    public function winerLists()
    {
        return $this->belongsTo('\app\admin\model\MinerLists', 'winer_id', 'id');
    }

    public function memberUser()
    {
        return $this->belongsTo('\app\admin\model\MemberUser', 'uid', 'id');
    }

}