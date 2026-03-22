<?php

namespace app\admin\controller\system;


use app\admin\model\SystemAdmin;
use app\admin\model\MemberUser;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\FoxCommon;

/**
 * Class Admin
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="系统：管理团队")
 */
class Admin extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemAdmin();
        $this->modelu = new MemberUser();
        if($this->adminInfo['is_team']==0){
            if ($this->adminInfo['id'] == AdminConstant::SUPER_ADMIN_ID) {
                $this->assign('auth_list', $this->model->getAuthList());
            }else{
                $this->assign('auth_list', $this->model->getAuthListt($this->adminInfo['auth_ids']));
            }
        }else{
            $this->assign('auth_list', $this->model->getAuthListTeam($this->adminInfo['auth_ids']));
        }
        
    }

    /**
     * @NodeAnotation(title="列表")
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
                ->withJoin('adminAuth', 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin('adminAuth', 'LEFT')
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
                        $list[$k]['holder_name'] = $this->model->where('id', $v['holder_id'])->value('username');
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
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $authIds = $this->request->post('auth_ids', []);
            if(count(array_keys($authIds)) <> 1){
                $this->error('选择一种权限');
            }
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $post['is_team'] = $this->model->getAuthTeam($post['auth_ids']);
            $is_front = $this->model->getAuthFront($post['auth_ids']);
            if($this->adminInfo['is_team']==1){
                $post['level_id'] = $this->adminInfo['id'];
                $post['level_ids'] = FoxCommon::adminup_level_ids_arr($this->adminInfo['id'],1);
                $post['holder_id'] = FoxCommon::top_adminup_level_ids_arr($this->adminInfo['id']);
            }
            $password = sysconfig('base','member_pwd'); //默认密码
            $post['password'] = password($password);
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
                $lastId = $this->model->id;
                if($is_front){
                    $post['admin_id'] = $lastId;
                    $post['group_id'] = sysconfig('base','member_group');
                    $saveu = $this->modelu->save($post);
                    $lastIdu = $this->modelu->id;
                    $invite_code =FoxCommon::only_invite_code($lastIdu);
                    $this->modelu->update(['invite_code'=>$invite_code,'id'=>$lastIdu]);
                    $this->model->update(['member_id'=>$lastIdu,'id'=>$lastId]);
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if($row['member_id'] > 0){
            if($this->adminInfo['is_team']==0){
                $this->error('团队组才能操作');
            }
        }
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $authIds = $this->request->post('auth_ids', []);
            if(count(array_keys($authIds)) <> 1){
                $this->error('选择一种权限');
            }
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $post['is_team'] = $this->model->getAuthTeam($post['auth_ids']);
            
            $rule = [];
            $this->validate($post, $rule);
            if (isset($row['password'])) {
                unset($row['password']);
            }
            try {
                $save = $row->save($post);
                TriggerService::updateMenu($id);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->auth_ids = explode(',', $row->auth_ids);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="设置密码")
     */
    public function password($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'password|登录密码'       => 'require',
                'password_again|确认密码' => 'require',
            ];
            $this->validate($post, $rule);
            if ($post['password'] != $post['password_again']) {
                $this->error('两次密码输入不一致');
            }
            try {
                $save = $row->save([
                    'password' => password($post['password']),
                ]);
                if($row['member_id'] > 0){
                    $this->modelu->update(['password' => password($post['password']),'id'=>$row['member_id']]);
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->auth_ids = explode(',', $row->auth_ids);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $this->error('删除失败');
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        $id == AdminConstant::SUPER_ADMIN_ID && $this->error('超级管理员不允许修改');
        if (is_array($id)){
            if (in_array(AdminConstant::SUPER_ADMIN_ID, $id)){
                $this->error('超级管理员不允许修改');
            }
        }
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        if ($post['id'] == AdminConstant::SUPER_ADMIN_ID && $post['field'] == 'status') {
            $this->error('超级管理员状态不允许修改');
        }
        $row = $this->model->find($post['id']);
        empty($row) && $this->error('数据不存在');
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }


}
