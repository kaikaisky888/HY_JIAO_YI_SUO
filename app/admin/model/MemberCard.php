<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-11 13:52:20
 * @LastEditTime: 2021-07-11 16:18:47
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class MemberCard extends TimeModel
{

    protected $name = "member_card";

    protected $deleteTime = false;

    public function memberUser()
    {
        return $this->belongsTo('\app\admin\model\MemberUser', 'uid', 'id');
    }
    

}