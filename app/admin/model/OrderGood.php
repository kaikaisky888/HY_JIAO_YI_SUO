<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-25 01:15:22
 * @LastEditTime: 2021-08-04 11:38:56
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class OrderGood extends TimeModel
{

    protected $name = "order_good";

    protected $deleteTime = "delete_time";

    
    public function goodLists()
    {
        return $this->belongsTo('\app\admin\model\GoodLists', 'good_id', 'id');
    }

    public function memberUser()
    {
        return $this->belongsTo('\app\admin\model\MemberUser', 'uid', 'id');
    }

}