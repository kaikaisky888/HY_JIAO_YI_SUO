<?php

namespace app\admin\model;

use think\Model;

class LoanLists extends Model
{
    protected $name = 'loan_lists';

    public function getStatusTextAttr($value, $data)
    {
        $map = [
            0 => '禁用',
            1 => '启用',
        ];
        return isset($map[$data['status']]) ? $map[$data['status']] : '';
    }
}