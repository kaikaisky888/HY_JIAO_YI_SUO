<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class MemberWallet extends TimeModel
{

    protected $name = "member_wallet";

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