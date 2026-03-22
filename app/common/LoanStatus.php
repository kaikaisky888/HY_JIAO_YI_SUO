<?php

namespace app\common;

class LoanStatus
{
    const CANCEL = -1;   // 已取消
    const WAIT = 0;      // 待审核
    const PASS = 1;      // 已放款
    const REJECT = 2;    // 已拒绝
    const FINISH = 4;    // 已结清
    const OVERDUE = 5;   // 已逾期

    public static function getMap()
    {
        return [
            self::CANCEL => '已取消',
            self::WAIT => '待审核',
            self::PASS => '已放款',
            self::REJECT => '已拒绝',
            self::FINISH => '已结清',
            self::OVERDUE => '已逾期',
        ];
    }
}