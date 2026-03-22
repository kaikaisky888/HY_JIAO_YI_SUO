<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-05-31 13:44:29
 * @LastEditTime: 2021-06-25 22:22:46
 * @Description: Forward, no stop
 */

namespace app\admin\controller\system;


use app\admin\model\SystemConfig;
use app\admin\model\SystemConfigCate;
use app\admin\service\TriggerService;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Config
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="系统：配置管理")
 */
class Config extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'asc',
    ];

    protected $csort = [
        'group' => 'desc',
        'sort' => 'desc',
        'id'   => 'asc',
    ];
    
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemConfig();
        $this->modelc = new SystemConfigCate();
    }

    /**
     * @NodeAnotation(title="分组列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            $count = $this->modelc->count();
            $list = $this->modelc->order($this->sort)->select();
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
     * @NodeAnotation(title="配置组列表")
     */
    public function lists()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }

            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($this->csort)
                ->page($page, $limit)
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
     * @NodeAnotation(title="添加分组")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'remark|分组名称' => 'require',
                'name|分组标识' => 'require|unique:system_config_cate',
            ];
            $this->validate($post, $rule);
            try {
                $save = $this->modelc->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑分组")
     */
    public function edit($id)
    {
        $row = $this->modelc->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'remark|分组名称' => 'require',
                'name|分组标识' => 'require|unique:system_config_cate',
            ];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $this->assign([
            'id'          => $id,
            'row'         => $row,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除分组")
     */
    public function delete()
    {}

    /**
     * @NodeAnotation(title="添加配置")
     */
    public function addconfig()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'group|配置分组' => 'require',
                'remark|配置名称' => 'require',
                'name|配置标识' => 'require|unique:system_config',
                'type|类型' => 'require',
            ];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
                TriggerService::updateMenu();
                TriggerService::updateSysconfig();
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $cateList = $this->modelc->select();
        $this->assign('cateList', $cateList);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="设置配置")
     */
    public function setconfig($id)
    {
        $row = $this->model->find($id);
        if($row['type']=='radio'){
            $row['sets'] = explode(',',$row['sets']);
        }
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
                TriggerService::updateMenu();
                TriggerService::updateSysconfig();
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign([
            'id'          => $id,
            'row'         => $row,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑配置")
     */
    public function editconfig($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'group|配置分组' => 'require',
                'remark|配置名称' => 'require',
                'name|配置标识' => 'require|unique:system_config',
                'type|类型' => 'require',
            ];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
                TriggerService::updateMenu();
                TriggerService::updateSysconfig();
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $cateList = $this->modelc->select();
        $this->assign('cateList', $cateList);
        $this->assign([
            'id'          => $id,
            'row'         => $row,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除配置")
     */
    public function deleteconfig()
    {}

   

}