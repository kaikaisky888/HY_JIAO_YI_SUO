<?php

namespace app\admin\model;

use think\Model;

class LoanOrder extends Model
{
    protected $name = 'loan_order';

    public function getStatusTextAttr($value, $data)
    {
        $map = [
            -1 => '已取消',
            0  => '待审核',
            1  => '已放款',
            2  => '已拒绝',
            4  => '已结清',
            5  => '已逾期',
        ];
        return isset($map[$data['status']]) ? $map[$data['status']] : '';
    }
}