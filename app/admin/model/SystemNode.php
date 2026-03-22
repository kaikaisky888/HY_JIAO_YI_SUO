<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-06-25 21:40:36
 * @Description: Forward, no stop
 */

namespace app\admin\model;


use app\common\model\TimeModel;

class SystemNode extends TimeModel
{

    public function getNodeTreeList()
    {
        $list = $this->order('node')->select()->toArray();
        $list = $this->buildNodeTree($list);
        return $list;
    }

    protected function buildNodeTree($list)
    {
        $newList = [];
        $repeatString = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        foreach ($list as $vo) {
            if ($vo['type'] == 1) {
                $newList[] = $vo;
                foreach ($list as $v) {
                    if ($v['type'] == 2 && strpos($v['node'], $vo['node'] . '/') !== false) {
                        $v['node'] = "{$repeatString}├{$repeatString}" . $v['node'];
                        $newList[] = $v;
                    }
                }
            }
        }
        return $newList;
    }


}