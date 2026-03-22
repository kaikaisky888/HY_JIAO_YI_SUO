<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-29 00:29:47
 * @LastEditTime: 2021-08-02 15:56:34
 * @Description: Forward, no stop
 */

namespace app\admin\controller\order;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="订单：期权订单管理")
 */
class Seconds extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;
    
    protected $allowModifyFields = [
        'status', 'remark', 'is_delete'
    ];

    protected $sort = [
        'update_time' => 'desc',
        'id'   => 'desc',
    ];
    
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\OrderSeconds();
        
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
                list($page, $limit, $where) = $this->buildTableParames([], $this->adminInfo['id'], 'memberUser');
            }else{
                list($page, $limit, $where) = $this->buildTableParames();
            }
            $count = $this->model
                ->withJoin(['productLists', 'memberUser'], 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin(['productLists', 'memberUser'], 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
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
     * @NodeAnotation(title="单控")
     */
    public function kongone()
    {
        if(request()->isPost()){
            $id=request()->post('id','','intval');
            $value=request()->post('value','','intval');
            $info=$this->model->find($id);
            if(empty($info)){
                return $this->error('信息不存在');
            }
            $this->model->where('op_status', 0)->where('id', $id)->update(['kong_type'=>$value]);
        }
    }

    /**
     * @NodeAnotation(title="一键控赢")
     */
    public function konga()
    {
        if(request()->isPost()){
            $ids=request()->post('id','','trim');
            $value=1;
            $this->model->where('op_status', 0)->where('id', 'in', $ids)->update(['kong_type'=>$value]);
            $this->success('执行成功');
        }
    }

    /**
     * @NodeAnotation(title="一键控亏")
     */
    public function kongb()
    {
        if(request()->isPost()){
            $ids=request()->post('id','','trim');
            $value=2;
            $this->model->where('op_status', 0)->where('id', 'in', $ids)->update(['kong_type'=>$value]);
            $this->success('执行成功');
        }
    }
    
}