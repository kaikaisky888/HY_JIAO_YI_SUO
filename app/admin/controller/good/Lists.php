<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 00:09:27
 * @LastEditTime: 2026-03-13
 * @Description: Forward, no stop
 */

namespace app\admin\controller\good;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="功能：理财产品管理")
 */
class Lists extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;

    protected $sort = [
        'product_id' => 'desc',
        'sort'   => 'asc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\GoodLists();
        $this->assign('getProductLists', $this->model->getProductLists());
        $this->modelang = new \app\admin\model\LangLists();
        $this->assign('lang_list',$this->lang_list);
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

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $logo = $this->request->post('logo', []);
            $title = $this->request->post('title', []);
            $content = $this->request->post('content', []);
            $remark = $this->request->post('remark', []);

            $data = [];
            $data['play_time'] = $post['play_time'];
            $data['play_price'] = $post['play_price'];
            $data['max_price'] = $post['max_price'];
            $data['play_rate'] = $post['play_rate'];
            $data['annual_rate'] = isset($post['annual_rate']) ? trim($post['annual_rate']) : '';
            $data['sort'] = $post['sort'];
            $data['product_id'] = $post['product_id'];
            $data['can_buy'] = $post['can_buy'];

            foreach($this->lang_list as $k => $v){
                if($v == sysconfig('base','base_lang')){
                    if(empty($title[$v])){
                        $this->error($v.'标题不能为空');
                    }
                    if(empty($logo[$v])){
                        $this->error($v.'图片不能为空');
                    }
                    if(empty($content[$v])){
                        $this->error($v.'内容不能为空');
                    }
                    $data['lang'] = $v;
                    $data['title'] = $title[$v];
                    $data['logo'] = $logo[$v];
                    $data['content'] = $content[$v];
                    $data['remark'] = $remark[$v];
                }
            }

            $rule = [];
            $this->validate($data, $rule);

            try {
                $save = $this->model->save($data);
                $lastId = $this->model->id;
                if($lastId){
                    $langdata = [];
                    foreach($this->lang_list as $k => $v){
                        $langdata[] = [
                            'item' => 'good',
                            'item_id' => $lastId,
                            'lang' => $v,
                            'title' => $title[$v],
                            'logo' => $logo[$v],
                            'content' => $content[$v],
                            'remark' => $remark[$v],
                        ];
                    }
                    $this->modelang->saveAll($langdata);
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

        $langcon = [];
        foreach($this->lang_list as $k => $v){
            if($langinfo = $this->modelang->where('item','good')->where('item_id', $row['id'])->where('lang', $v)->find()){
                $langcon[$v] = $langinfo->toArray();
            }else{
                $langcon[$v] = [
                    'title' => '',
                    'logo' => '',
                    'content' => '',
                    'remark' => '',
                ];
            }
        }

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $logo = $this->request->post('logo', []);
            $title = $this->request->post('title', []);
            $content = $this->request->post('content', []);
            $remark = $this->request->post('remark', []);

            $data = [];
            $data['play_time'] = $post['play_time'];
            $data['play_price'] = $post['play_price'];
            $data['max_price'] = $post['max_price'];
            $data['play_rate'] = $post['play_rate'];
            $data['annual_rate'] = isset($post['annual_rate']) ? trim($post['annual_rate']) : '';
            $data['sort'] = $post['sort'];
            $data['product_id'] = $post['product_id'];
            $data['can_buy'] = $post['can_buy'];

            foreach($this->lang_list as $k => $v){
                if($v == sysconfig('base','base_lang')){
                    if(empty($title[$v])){
                        $this->error($v.'标题不能为空');
                    }
                    if(empty($logo[$v])){
                        $this->error($v.'图片不能为空');
                    }
                    if(empty($content[$v])){
                        $this->error($v.'内容不能为空');
                    }
                    $data['lang'] = $v;
                    $data['title'] = $title[$v];
                    $data['logo'] = $logo[$v];
                    $data['content'] = $content[$v];
                    $data['remark'] = $remark[$v];
                }
            }

            $rule = [];
            $this->validate($data, $rule);

            try {
                $this->modelang->where('item','good')->where('item_id', $id)->delete();
                $save = $this->model->update($data, ['id' => $id]);

                $langdata = [];
                foreach($this->lang_list as $k => $v){
                    $langdata[] = [
                        'item' => 'good',
                        'item_id' => $id,
                        'lang' => $v,
                        'title' => $title[$v],
                        'logo' => $logo[$v],
                        'content' => $content[$v],
                        'remark' => $remark[$v],
                    ];
                }
                $this->modelang->saveAll($langdata);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }

            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        $this->assign('row', $row);
        $this->assign('langcon', $langcon);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->find($id);
            empty($row) && $this->error('数据不存在');
            try {
                $save = $row->delete();
                if($save){
                    $this->modelang->where('item','good')->where('item_id', $id)->delete();
                }
            } catch (\Exception $e) {
                $this->error('删除失败');
            }
            $save ? $this->success('删除成功') : $this->error('删除失败');
        }
    }

}