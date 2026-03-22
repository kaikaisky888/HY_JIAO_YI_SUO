<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 21:09:01
 * @LastEditTime: 2021-06-26 21:36:45
 * @Description: Forward, no stop
 */

namespace app\admin\controller\miner;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="功能：矿机管理列表")
 */
class Lists extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\MinerLists();
        $this->assign('getProductLists', $this->model->getProductLists());

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
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->withJoin('productLists', 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin('productLists', 'LEFT')
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