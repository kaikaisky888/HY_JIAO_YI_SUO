<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 22:21:06
 * @LastEditTime: 2021-08-08 12:35:31
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
 * @ControllerAnnotation(title="会员：会员关系图")
 */
class Ship extends AdminController
{

    use \app\admin\traits\Curd;

    // protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MemberUser();
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
            $where[] = ['admin_id','<>',0];
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order('create_time','desc')
                ->select();
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['ucount'] = $this->model->where('level_id', $v['id'])->count('id');
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

    public function find()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post('', null, null);
            $id = $post['id'];
            $where[] = ['level_id','=',$id];
            $where[] = ['admin_id','=',0];
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->order('create_time','desc')
                ->select();
            if($list){
                foreach($list as $k => $v){
                    $list[$k]['ucount'] = $this->model->where('level_id', $v['id'])->count('id');
                }
            }
            $data = [
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
    }
    
}