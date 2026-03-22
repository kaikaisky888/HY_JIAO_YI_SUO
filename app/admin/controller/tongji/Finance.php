<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-10-15 21:10:50
 * @Description: Forward, no stop
 */

namespace app\admin\controller\tongji;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\FoxCommon;
use think\facade\Db;
use app\common\FoxKline;

/**
 * @ControllerAnnotation(title="统计：财务统计")
 */
class Finance extends AdminController
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
            
            $prolist = \app\admin\model\ProductLists::where('withdraw_member',1)->where('status',1)->field('id,title,close')->order('base','desc')->order('sort','desc')->select();
            $list = [];
            if($prolist){
                foreach($prolist as $k => $v){
                    $list[$k]['title'] = $v['title'];
                    $list[$k]['recharge'] = Db::name('member_wallet_log')
                    ->alias('a')
                    ->where('a.type',1)
                    ->where('a.status',2)
                    ->where('a.product_id',$v['id'])
                    ->join('member_user m','a.uid = m.id')
                    ->where('m.is_test',0)
                    ->where($where)
                    ->sum('a.account');
                    $list[$k]['recharge_usd'] = FoxKline::get_me_price_usdt_to_usd_close($list[$k]['recharge'],$v['close'],8);
                    $list[$k]['withdraw'] = Db::name('member_wallet_log')
                    ->alias('a')
                    ->where('a.type',2)
                    ->where('a.status',2)
                    ->where('a.product_id',$v['id'])
                    ->join('member_user m','a.uid = m.id')
                    ->where('m.is_test',0)
                    ->where($where)
                    ->sum('a.account');
                    $list[$k]['withdraw_usd'] = FoxKline::get_me_price_usdt_to_usd_close($list[$k]['withdraw'],$v['close'],8);
                    $list[$k]['resault'] = bc_sub($list[$k]['recharge'],$list[$k]['withdraw']);
                    $list[$k]['resault_usd'] = bc_sub($list[$k]['recharge_usd'],$list[$k]['withdraw_usd']);
                }
            }
            
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