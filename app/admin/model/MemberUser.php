<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class MemberUser extends TimeModel
{

    protected $name = "member_user";

    protected $deleteTime = "delete_time";

    
    public function memberGroup()
    {
        return $this->belongsTo('\app\admin\model\MemberGroup', 'group_id', 'id');
    }

    

}