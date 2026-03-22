<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-07-29 00:29:47
 * @LastEditTime: 2021-08-08 18:27:37
 * @Description: Forward, no stop
 */

namespace app\admin\controller\order;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="订单：合约订单管理")
 */
class Leverdeal extends AdminController
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

        $this->model = new \app\admin\model\OrderLeverdeal();
        
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


}