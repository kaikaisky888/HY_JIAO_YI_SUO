<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-09-28 17:24:06
 * @Description: Forward, no stop
 */

namespace app\admin\model;


use app\common\model\TimeModel;

class SystemAdmin extends TimeModel
{

    protected $deleteTime = 'delete_time';

    public function adminAuth()
    {
        return $this->belongsTo('\app\admin\model\SystemAuth', 'auth_ids', 'id');
    }

    public function getAuthList()
    {
        $list = (new SystemAuth())
            ->where('status', 1)
            ->where('is_front', 0)
            ->column('title', 'id');
        return $list;
    }

    public function getAuthListt($auth_ids)
    {
        $list = (new SystemAuth())
            ->where('id', '>', $auth_ids)
            ->where('status', 1)
            ->where('is_front', 0)
            ->column('title', 'id');
        return $list;
    }

    public function getAuthListTeam($auth_ids)
    {
        $list = (new SystemAuth())
            ->where('id', '>', $auth_ids)
            ->where('status', 1)
            ->where('is_team', 1)
            ->column('title', 'id');
        return $list;
    }

    public function getAuthTeam($id)
    {
        $row = (new SystemAuth())
        ->where('id',$id)
        ->value('is_team');
        return $row;
    }

    public function getAuthFront($id)
    {
        $row = (new SystemAuth())
        ->where('id',$id)
        ->value('is_front');
        return $row;
    }

}