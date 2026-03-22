<?php

namespace app\admin\controller\product;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\admin\model\ProductCate;
use think\App;
use app\common\service\KlineService;

/**
 * @ControllerAnnotation(title="功能：产品列表管理")
 */
class Lists extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;
    
    protected $allowModifyFields = [
        'is_kong','status', 'sort', 'remark', 'is_delete'
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\ProductLists();
        
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
                ->withJoin('productCate', 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin('productCate', 'LEFT')
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
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $types = $this->request->post('types', []);
            $post['types'] = implode(',', array_keys($types));
            if($this->model->where('status',1)->where('base',1)->find() && $post['base']==1){
                $this->error('基础币已存在');
            }
            if($this->model->where('status',1)->where('is_kong',1)->find() && $post['is_kong']==1){
                $this->error('空气币已存在');
            }
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
                if($post['code']){
                    $es_table = 'market_'.$post['code'].'_kline_1min';
                    KlineService::instance()->detectTable($es_table);
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $catelist = ProductCate::where('status',1)->select();
        $this->assign('catelist', $catelist);
        $coin_types = \think\facade\Config::get('allset.coin_types');
        $this->assign('coin_types', $coin_types);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $types = $this->request->post('types', []);
            $post['types'] = implode(',', array_keys($types));
            if($this->model->where('status',1)->where('base',1)->where('id','<>',$id)->find() && $post['base']==1){
                $this->error('基础币已存在');
            }
            if($this->model->where('status',1)->where('is_kong',1)->where('id','<>',$id)->find() && $post['is_kong']==1){
                $this->error('空气币已存在');
            }
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
                if($post['code']){
                    $es_table = 'market_'.$post['code'].'_kline_1min';
                    KlineService::instance()->detectTable($es_table);
                }
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->types = explode(',', $row->types);
        $this->assign('row', $row);
        $catelist = ProductCate::where('status',1)->select();
        $this->assign('catelist', $catelist);
        $coin_types = \think\facade\Config::get('allset.coin_types');
        $this->assign('coin_types', $coin_types);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="设置产品属性")
     */
    public function setpro($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->types = explode(',', $row->types);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="空气币")
     */
    public function kong()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->withJoin('productCate', 'LEFT')
                ->where($where)
                ->where('is_kong',1)
                ->count();
            $list = $this->model
                ->withJoin('productCate', 'LEFT')
                ->where($where)
                ->where('is_kong',1)
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
     * @NodeAnotation(title="设置空气币")
     */
    public function ekong($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

}