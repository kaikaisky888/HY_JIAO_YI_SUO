<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-25 01:15:22
 * @LastEditTime: 2021-09-13 15:46:52
 * @Description: Forward, no stop
 */

namespace app\admin\model;

use app\common\model\TimeModel;

class MemberWalletData extends TimeModel
{

    protected $name = "member_wallet_data";

    protected $deleteTime = "delete_time";
    
    public function productLists()
    {
        return $this->belongsTo('\app\admin\model\ProductLists', 'product_id', 'id');
    }

    public function memberUser()
    {
        return $this->belongsTo('\app\admin\model\MemberUser', 'uid', 'id');
    }

    public function memberWallet()
    {
        return $this->belongsTo('\app\admin\model\MemberWallet', 'wallet_id', 'id');
    }

    public function adminUser()
    {
        return $this->belongsTo('\app\admin\model\SystemAdmin', 'douid', 'id');
    }
    

}