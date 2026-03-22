<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-10-15 21:11:15
 * @Description: Forward, no stop
 */

namespace app\admin\controller\tongji;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\FoxCommon;
use think\facade\Db;
/**
 * @ControllerAnnotation(title="统计：订单统计")
 */
class Order extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * @NodeAnotation(title="会员统计")
     */
    public function index()
    {
        
        if ($this->request->isAjax()) {
            $count_deal = 0;
            $count_seconds = 0;
            $count_leverdeal = 0;
            $count_good = 0;
            $count_ieorg = 0;
            $count_winer = 0;

            $times = request()->post('times',null);
            $username = request()->post('username',null);
            $where = [];
            if($times){
                [$beginTime, $endTime] = explode(' - ', $times);
                $where[] = ['a.create_time', '>=', strtotime($beginTime. ' 00:00:00')];
                $where[] = ['a.create_time', '<=', strtotime($endTime. ' 23:59:59')];
            }
            if(session('admin.is_team')==1){
                $where[] = ['','EXP',Db::raw("FIND_IN_SET(".session('admin.id').",m.level_ids)")];
            }
            if($username){
                $where[] = ['m.username', 'like', $username];
            }

            if($username){
                $user = \app\admin\model\MemberUser::where('username',$username)->where('is_test',0)->find();
                if(!$user){
                    return $this->error('没有这个用户');
                }
                if(session('admin.is_team')==1){
                    if(!in_array(session('admin.id'), explode(',',$user->level_ids))){
                        return $this->error('没有这个用户');
                    }
                }
            }
            
            $count_deal = Db::name('order_deal')
            ->alias('a')
            ->join('member_user m','a.uid = m.id')
            ->where('m.is_test',0)
            ->where($where)
            ->count('a.id');
            $count_seconds = Db::name('order_seconds')
            ->alias('a')
            ->join('member_user m','a.uid = m.id')
            ->where('m.is_test',0)
            ->where($where)
            ->count('a.id');
            $count_leverdeal = Db::name('order_leverdeal')
            ->alias('a')
            ->join('member_user m','a.uid = m.id')
            ->where('m.is_test',0)
            ->where($where)
            ->count('a.id');
            $count_good = Db::name('order_good')
            ->alias('a')
            ->join('member_user m','a.uid = m.id')
            ->where('m.is_test',0)
            ->where($where)
            ->count('a.id');
            $count_ieorg = Db::name('order_ieorg')
            ->alias('a')
            ->join('member_user m','a.uid = m.id')
            ->where('m.is_test',0)
            ->where($where)
            ->count('a.id');
            $count_winer = Db::name('order_winer')
            ->alias('a')
            ->join('member_user m','a.uid = m.id')
            ->where('m.is_test',0)
            ->where($where)
            ->count('a.id');
            $list['count_deal']=$count_deal;
            $list['count_seconds']=$count_seconds;
            $list['count_leverdeal']=$count_leverdeal;
            $list['count_good']=$count_good;
            $list['count_ieorg']=$count_ieorg;
            $list['count_winer']=$count_winer;
            $data = [
                'code'  => 1,
                'msg'   => '',
                'data'  => $list,
            ];
            return json($data);
        }
        $this->assign('admin', session('admin'));
        return $this->fetch();
    }

    
}