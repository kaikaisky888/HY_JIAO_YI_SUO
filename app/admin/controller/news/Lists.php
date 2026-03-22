<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-19 22:59:23
 * @LastEditTime: 2021-09-29 12:32:18
 * @Description: Forward, no stop
 */

namespace app\admin\controller\news;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="功能：新闻频道")
 */
class Lists extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\NewsLists();
        $this->modelang = new \app\admin\model\LangLists();
       
        $this->assign('getNewsCateList', $this->model->getNewsCateList());
        $this->assign('lang_list',$this->lang_list);

    }

    
    /**
     * @NodeAnotation(title="新闻列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->withJoin('newsCate', 'LEFT')
                ->where($where)
                ->count();
            $list = $this->model
                ->withJoin('newsCate', 'LEFT')
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
            $data =[];
            $data['cate_id'] = $post['cate_id'];
            $data['name'] = $post['name'];
            $data['sort'] = $post['sort'];
            
            foreach($this->lang_list as $k => $v){
                if(empty($title[$v])){
                    $this->error($v.'标题不能为空');
                }
                // if(empty($logo[$v])){
                //     $this->error($v.'图片不能为空');
                // }
                // if(empty($content[$v])){
                //     $this->error($v.'内容不能为空');
                // }
                if($v == sysconfig('base','base_lang')){
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
                            'item' => 'news',
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
            if($langinfo = $this->modelang->where('item','news')->where('item_id', $row['id'])->where('lang', $v)->find()){
                $langcon[$v] = $langinfo->ToArray();
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
            $data =[];
            $data['cate_id'] = $post['cate_id'];
            $data['name'] = $post['name'];
            $data['sort'] = $post['sort'];
            
            foreach($this->lang_list as $k => $v){
                if(empty($title[$v])){
                    $this->error($v.'标题不能为空');
                }
                // if(empty($logo[$v])){
                //     $this->error($v.'图片不能为空');
                // }
                // if(empty($content[$v])){
                //     $this->error($v.'内容不能为空');
                // }
                if($v == sysconfig('base','base_lang')){
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
                $this->modelang->where('item','news')->where('item_id', $id)->delete();
                $save = $this->model->update($data,['id'=>$id]);
                $langdata = [];
                foreach($this->lang_list as $k => $v){
                    $langdata[] = [
                        'item' => 'news',
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
                    $this->modelang->where('item','news')->where('item_id', $id)->delete();
                }
            } catch (\Exception $e) {
                $this->error('删除失败');
            }
            $save ? $this->success('删除成功') : $this->error('删除失败');
        }
    }
    
}