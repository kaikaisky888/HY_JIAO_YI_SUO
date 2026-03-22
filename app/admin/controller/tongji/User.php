<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-10-15 21:01:05
 * @Description: Forward, no stop
 */

namespace app\admin\controller\tongji;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\FoxCommon;

/**
 * @ControllerAnnotation(title="统计：会员统计")
 */
class User extends AdminController
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
            $count_a = 0;
            $count_b = 0;
            $count_c = 0;

            $times = request()->post('times',null);
            $where = [];
            if($times){
                [$beginTime, $endTime] = explode(' - ', $times);
                $where[] = ['create_time', '>=', strtotime($beginTime. ' 00:00:00')];
                $where[] = ['create_time', '<=', strtotime($endTime. ' 23:59:59')];
            }
            
            if(session('admin.is_team')==0){
                $count_a = \app\admin\model\SystemAdmin::where('is_team',1)->where('holder_id',0)->where($where)->count('id');
                $count_b = \app\admin\model\MemberUser::where('admin_id','>',0)->where($where)->count('id');
                $count_c = \app\admin\model\MemberUser::where('admin_id',0)->where('is_test',0)->where($where)->count('id');
            }elseif(session('admin.is_team')==1 && session('admin.member_id') == 0){
                $count_a = \app\admin\model\SystemAdmin::where('is_team',1)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->where($where)->count('id');
                $count_b = \app\admin\model\MemberUser::where('admin_id','>',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->where($where)->count('id');
                $count_c = \app\admin\model\MemberUser::where('admin_id',0)->where('is_test',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->where($where)->count('id');
            }elseif(session('admin.is_team')==1 && session('admin.member_id') > 0){
                $count_c = \app\admin\model\MemberUser::where('admin_id',0)->where('is_test',0)->whereRaw('FIND_IN_SET('.session('admin.id').',level_ids)')->where($where)->count('id');
            }
            $list['count_a']=$count_a;
            $list['count_b']=$count_b;
            $list['count_c']=$count_c;
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