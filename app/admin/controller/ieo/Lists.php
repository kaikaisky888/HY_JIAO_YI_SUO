<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-26 22:41:00
 * @LastEditTime: 2021-08-04 17:03:31
 * @Description: Forward, no stop
 */

namespace app\admin\controller\ieo;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use app\common\FoxCommon;
use app\common\FoxKline;

/**
 * @ControllerAnnotation(title="功能：认购发行")
 */
class Lists extends AdminController
{

    use \app\admin\traits\Curd;

    protected $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\IeoLists();
        $this->modelang = new \app\admin\model\LangLists();

        $this->assign('getProductLists', $this->model->getProductLists());
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
            $data =[];
            if(empty($post['in_time'])){
                $this->error('请选择有效期范围');
            }
            if(empty($post['ieo_usdt_price']) || $post['ieo_usdt_price'] <= 0){
                $this->error('请填写有效发行价');
            }
            if(empty($post['ieo_num']) || $post['ieo_num'] <= 0){
                $this->error('请选择有效发行数量');
            }
            [$beginTime, $endTime] = explode(' - ', $post['in_time']);
            $data['product_id'] = $post['product_id'];
            $data['ieo_usdt_price'] = isset($post['ieo_usdt_price'])?$post['ieo_usdt_price']:0;
            $data['ieo_btc_price'] = !empty($post['ieo_btc_price'])?$post['ieo_btc_price']:FoxKline::get_me_price_usdt_to($data['ieo_usdt_price'],'btcusdt');
            $data['ieo_eth_price'] = !empty($post['ieo_eth_price'])?$post['ieo_eth_price']:FoxKline::get_me_price_usdt_to($data['ieo_usdt_price'],'ethusdt');
            if(empty($data['ieo_btc_price']) || $data['ieo_btc_price'] <= 0){
                $this->error('自动生成发行价B失败或填写错误');
            }
            if(empty($data['ieo_eth_price']) || $data['ieo_eth_price'] <= 0){
                $this->error('自动生成发行价E失败或填写错误');
            }
            $data['ieo_num'] = $post['ieo_num'];
            $data['start_time'] = strtotime($beginTime.' 00:00:00');
            $data['end_time'] = strtotime($endTime.' 23:59:59');
            $data['ieo_site'] = $post['ieo_site'];
            $data['ieo_link'] = $post['ieo_link'];
            $data['sort'] = $post['sort'];
            $data['coin_title'] = $post['coin_title'];
                        
            foreach($this->lang_list as $k => $v){
                if(empty($title[$v])){
                    $this->error($v.'标题不能为空');
                }
                // if(empty($logo[$v])){
                //     $this->error($v.'图片不能为空');
                // }
                if(empty($content[$v])){
                    $this->error($v.'内容不能为空');
                }
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
                            'item' => 'ieo',
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
            if($langinfo = $this->modelang->where('item','ieo')->where('item_id', $row['id'])->where('lang', $v)->find()){
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
            if(empty($post['in_time'])){
                $this->error('请选择有效期范围');
            }
            if(empty($post['ieo_usdt_price']) || $post['ieo_usdt_price'] <= 0){
                $this->error('请填写有效发行价');
            }
            if(empty($post['ieo_num']) || $post['ieo_num'] <= 0){
                $this->error('请选择有效发行数量');
            }
            [$beginTime, $endTime] = explode(' - ', $post['in_time']);
            $data['product_id'] = $post['product_id'];
            $data['ieo_usdt_price'] = isset($post['ieo_usdt_price'])?$post['ieo_usdt_price']:0;
            $data['ieo_btc_price'] = !empty($post['ieo_btc_price'])?$post['ieo_btc_price']:FoxKline::get_me_price_usdt_to($data['ieo_usdt_price'],'btcusdt');
            $data['ieo_eth_price'] = !empty($post['ieo_eth_price'])?$post['ieo_eth_price']:FoxKline::get_me_price_usdt_to($data['ieo_usdt_price'],'ethusdt');
            if(empty($data['ieo_btc_price']) || $data['ieo_btc_price'] <= 0){
                $this->error('自动生成发行价B失败或填写错误');
            }
            if(empty($data['ieo_eth_price']) || $data['ieo_eth_price'] <= 0){
                $this->error('自动生成发行价E失败或填写错误');
            }
            $data['ieo_num'] = $post['ieo_num'];
            $data['start_time'] = strtotime($beginTime.' 00:00:00');
            $data['end_time'] = strtotime($endTime.' 23:59:59');
            $data['ieo_site'] = $post['ieo_site'];
            $data['ieo_link'] = $post['ieo_link'];
            $data['sort'] = $post['sort'];
            $data['coin_title'] = $post['coin_title'];
            
            foreach($this->lang_list as $k => $v){
                if(empty($title[$v])){
                    $this->error($v.'标题不能为空');
                }
                // if(empty($logo[$v])){
                //     $this->error($v.'图片不能为空');
                // }
                if(empty($content[$v])){
                    $this->error($v.'内容不能为空');
                }
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
                $this->modelang->where('item','ieo')->where('item_id', $id)->delete();
                $save = $this->model->update($data,['id'=>$id]);
                $langdata = [];
                foreach($this->lang_list as $k => $v){
                    $langdata[] = [
                        'item' => 'ieo',
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
                    $this->modelang->where('item','ieo')->where('item_id', $id)->delete();
                }
            } catch (\Exception $e) {
                $this->error('删除失败');
            }
            $save ? $this->success('删除成功') : $this->error('删除失败');
        }
    }
}