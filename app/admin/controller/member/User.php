<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-10-06 20:03:41
 * @Description: Forward, no stop
 */

namespace app\admin\controller\member;

use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\FoxCommon;

/**
 * @ControllerAnnotation(title="会员：会员管理")
 */
class User extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;
    protected $modela;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberUser();
        $this->modela = new \app\admin\model\SystemAdmin();
    }

    
    /**
     * @NodeAnotation(title="会员列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            if($this->adminInfo['is_team']==1){
                list($page, $limit, $where) = $this->buildTableParames([],$this->adminInfo['id']);
            }else{
                list($page, $limit, $where) = $this->buildTableParames();
            }
            $count = $this->model
                ->withJoin('memberGroup', 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin('memberGroup', 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            if($list){
                foreach($list as $k => $v){
                    if($v['level_id'] > 0){
                        $list[$k]['level_name'] = $this->model->where('id', $v['level_id'])->value('username');
                    }
                    if($v['holder_id'] > 0){
                        $list[$k]['holder_name'] = $this->modela->where('id', $v['holder_id'])->value('username');
                    }
                }
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加会员")
     */
    public function add()
    {
        $is_team = $this->adminInfo['is_team'];
        $is_member = $this->adminInfo['member_id'];
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if($post['compassword'] <> $post['password']){
                $this->error('两次密码不一致');
            }
            unset($post['compassword']);
            $password = $post['password']; //默认密码
            $post['password'] = password($password);
            if($is_team){
                if($is_member){
                    $post['level_id'] = $this->adminInfo['member_id'];
                    $post['level_ids'] = FoxCommon::adminup_level_ids_arr($this->adminInfo['id'],1);
                    $post['holder_id'] = FoxCommon::top_adminup_level_ids_arr($this->adminInfo['id']);
                }else{
                    $level_name=!empty($post['level_name'])?$post['level_name']:'';
                    if(!$level_name){
                        $this->error('必须对应业务员');
                    }
                    $row = $this->modela->where('username',$level_name)->find();
                    if(!$row){
                        $this->error('对应业务员不存在');
                    }
                    unset($post['level_name']);
                    $post['level_id'] = $row['member_id'];
                    $post['level_ids'] = $row['level_ids'].','.$row['id'];
                    $post['holder_id'] = $row['holder_id'];
                }
            }
            $post['group_id'] = sysconfig('base','member_group');
            
            $rule = [
                'username|帐户' => 'require|unique:member_user',
            ];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
                $lastId = $this->model->id;
                $invite_code =FoxCommon::only_invite_code($lastId);
                $this->model->update(['invite_code'=>$invite_code,'id'=>$lastId]);
                FoxCommon::check_member_wallet($lastId);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('is_team', $is_team);
        $this->assign('is_member', $is_member);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑会员")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        FoxCommon::check_member_wallet($id);
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if($post['compassword'] && $post['password']){
                if($post['compassword'] <> $post['password']){
                    $this->error('两次密码不一致');
                }
                unset($post['compassword']);
                $password = $post['password']; //默认密码
                $post['password'] = password($password);
            }else{
                unset($post['compassword']);
                unset($post['password']);
            }
            if($post['compaypwd'] && $post['paypwd']){
                if($post['compaypwd'] <> $post['paypwd']){
                    $this->error('两次交易密码不一致');
                }
                unset($post['compaypwd']);
                $paypwd = $post['paypwd']; //默认密码
                $post['paypwd'] = password($paypwd);
            }else{
                unset($post['compaypwd']);
                unset($post['paypwd']);
            }
            $rule = [];
            $this->validate($post, $rule);
            if (isset($row['password'])) {
                unset($row['password']);
            }
            if (isset($row['paypwd'])) {
                unset($row['paypwd']);
            }
            try {
                $save = $row->save($post);
                if($row['admin_id']>0){
                    $this->modela->update(['password' => password($post['password']),'id'=>$row['admin_id']]);
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="调整上级")
     */
    public function level($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if($row['admin_id']>0){
            $this->error('此用户为业务员');
        }
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $level_id = $post['level_id'];
            $user = \app\admin\model\MemberUser::where('id',$level_id)->field('holder_id,level_id,level_ids,admin_id')->find();
            $dpost['level_id'] = $level_id;
            $dpost['holder_id'] = $user['holder_id'];
            if(strpos($user['level_ids'],','.$user['admin_id']) !== false){ 
                $dpost['level_ids'] = $user['level_ids'];
            }else{
                $dpost['level_ids'] = $user['level_ids'].','.$user['admin_id'];
            }
            try {
                $save = $row->save($dpost);
                if($save){
                    $this->find_level_to($dpost['level_id'],$dpost['level_ids'],$dpost['holder_id']);
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row['leveler'] = FoxCommon::find_level_user_name($row['level_id']);
        $row['holder'] = FoxCommon::top_adminup_level_ids_name($row['holder_id']);
        $gudong = \app\admin\model\SystemAdmin::where('auth_ids',9)->field('id,username')->select();
        $this->assign('row', $row);
        $this->assign('gudong', $gudong);
        return $this->fetch();
    }

    public function find_level_to($level_id,$level_ids,$holder_id)
    {
        if (!$level_id) {
            return false;
        }
        $map['level_id'] = $level_id;
        $map['admin_id'] = 0;
        $list = \app\admin\model\MemberUser::field('id')->where($map)->select();
        foreach ($list as $key => $v) {
            \app\admin\model\MemberUser::where('id', $v["id"])->update(['level_ids' => $level_ids,'holder_id'=>$holder_id]);
            $this->find_level_to($v["id"],$level_ids,$holder_id);
        }
    }

    
}