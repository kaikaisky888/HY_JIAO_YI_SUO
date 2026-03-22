<?php

namespace app\admin\controller;

use app\admin\model\SystemUploadfile;
use app\admin\model\SystemAdmin;
use app\admin\model\MemberUser;
use app\common\controller\AdminController;
use app\common\service\MenuService;
use EasyAdmin\upload\Uploadfile;
use think\db\Query;
use think\facade\Cache;
use app\common\FoxCommon;
use app\common\FoxKline;

class Ajax extends AdminController
{

    /**
     * 初始化后台接口地址
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function initAdmin()
    {
        $cacheData = Cache::get('initAdmin_' . session('admin.id'));
        if (!empty($cacheData)) {
            return json($cacheData);
        }
        $menuService = new MenuService(session('admin.id'));
        $data = [
            'logoInfo' => [
                'title' => sysconfig('site', 'logo_title'),
                // 'image' => sysconfig('site', 'logo_image'),
                'image' => '/static/index/images/logo.png',
                'href'  => __url('index/index'),
            ],
            'homeInfo' => $menuService->getHomeInfo(),
            'menuInfo' => $menuService->getMenuTree(),
        ];
        Cache::tag('initAdmin')->set('initAdmin_' . session('admin.id'), $data);
        return json($data);
    }

    /**
     * 清理缓存接口
     */
    public function clearCache()
    {
        Cache::clear();
        $this->success('清理缓存成功');
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        $data = [
            'upload_type' => $this->request->post('upload_type'),
            'file'        => $this->request->file('file'),
        ];
        $uploadConfig = sysconfig('upload');
        empty($data['upload_type']) && $data['upload_type'] = $uploadConfig['upload_type'];
        $rule = [
            'upload_type|指定上传类型有误' => "in:{$uploadConfig['upload_allow_type']}",
            'file|文件'              => "require|file|fileExt:{$uploadConfig['upload_allow_ext']}|fileSize:{$uploadConfig['upload_allow_size']}",
        ];
        $this->validate($data, $rule);
        try {
            $upload = Uploadfile::instance()
                ->setUploadType($data['upload_type'])
                ->setUploadConfig($uploadConfig)
                ->setFile($data['file'])
                ->save();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        if ($upload['save'] == true) {
            $this->success($upload['msg'], ['url' => $upload['url']]);
        } else {
            $this->error($upload['msg']);
        }
    }

    /**
     * 上传图片至编辑器
     * @return \think\response\Json
     */
    public function uploadEditor()
    {
        $data = [
            'upload_type' => $this->request->post('upload_type'),
            'file'        => $this->request->file('upload'),
        ];
        $uploadConfig = sysconfig('upload');
        empty($data['upload_type']) && $data['upload_type'] = $uploadConfig['upload_type'];
        $rule = [
            'upload_type|指定上传类型有误' => "in:{$uploadConfig['upload_allow_type']}",
            'file|文件'              => "require|file|fileExt:{$uploadConfig['upload_allow_ext']}|fileSize:{$uploadConfig['upload_allow_size']}",
        ];
        $this->validate($data, $rule);
        try {
            $upload = Uploadfile::instance()
                ->setUploadType($data['upload_type'])
                ->setUploadConfig($uploadConfig)
                ->setFile($data['file'])
                ->save();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        if ($upload['save'] == true) {
            return json([
                'error'    => [
                    'message' => '上传成功',
                    'number'  => 201,
                ],
                'fileName' => '',
                'uploaded' => 1,
                'url'      => $upload['url'],
            ]);
        } else {
            $this->error($upload['msg']);
        }
    }

    /**
     * 获取上传文件列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUploadFiles()
    {
        $get = $this->request->get();
        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 10;
        $title = isset($get['title']) && !empty($get['title']) ? $get['title'] : null;
        $this->model = new SystemUploadfile();
        $count = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('original_name', 'like', "%{$title}%");
            })
            ->count();
        $list = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('original_name', 'like', "%{$title}%");
            })
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

    /**
     * 获取业务员列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllfronts()
    {
        $get = $this->request->get();
        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 10;
        $title = isset($get['title']) && !empty($get['title']) ? $get['title'] : null;
        $this->model = new SystemAdmin();
        $where[] = ['level_ids','like','%,'.$this->adminInfo['id'].'%'];
        $count = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('username', 'like', "%{$title}%");
            })
            ->where('status',1)
            ->where('member_id','>',0)
            ->where($where)
            ->count();
        $list = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('username', 'like', "%{$title}%");
            })
            ->where('status',1)
            ->where('member_id','>',0)
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

    public function get_product()
    {
        if(request()->isPost()){
            $code = request()->post('code/s','',"trim");
            if($code){
                $pro = \app\admin\model\ProductLists::where('code',$code)->where('status',1)->field('open,close,high,low,change,volume')->find();
                if($pro){
                    $pro['open'] = (float)$pro['open'];
                    $pro['close'] = (float)$pro['close'];
                    $pro['change'] = (float)$pro['change'];
                    $pro['high'] = (float)$pro['high'];
                    $pro['low'] = (float)$pro['low'];
                    $pro['vol'] = number_format($pro['volume'],4);
                    $pro['volume'] = number_format($pro['volume'],4);
                    $pro['usd'] = FoxKline::get_me_price_usdt_to_usd($pro['close']);
                    return json(['code'=>1,'data'=>$pro]);
                }
                return json(['code'=>0]);
            }
        }
        return json(['code'=>0]);
    }

    public function getdata()
    {
        if(request()->isPost()){
            $days = this_month_day();
            $now_day = date('d');
            $listday = [];
            $count_deal = [];
            $count_leverdeal = [];
            $count_seconds = [];
            $count_coinwin = [];
            $count_ieorg = [];
            $count_winer = [];
            for($i=1; $i<=$days; $i++){
                $listday[] = $i.'日';
                if($i<10){
                    $d='0'.$i;
                }else{
                    $d = $i;
                }
                $st = strtotime(date('Y-m-'.$d.' 00:00:00'));
                $et = strtotime(date('Y-m-'.$d.' 23:59:59'));
                $count_deal[] = \app\admin\model\OrderDeal::where('create_time', '>=', $st)->where('create_time', '<=', $et)->count('id');
                $count_leverdeal[] = \app\admin\model\OrderLeverdeal::where('create_time', '>=', $st)->where('create_time', '<=', $et)->count('id');
                $count_seconds[] = \app\admin\model\OrderSeconds::where('create_time', '>=', $st)->where('create_time', '<=', $et)->count('id');
                $count_coinwin[] = \app\admin\model\OrderGood::where('create_time', '>=', $st)->where('create_time', '<=', $et)->count('id');
                $count_ieorg[] = \app\admin\model\OrderIeorg::where('create_time', '>=', $st)->where('create_time', '<=', $et)->count('id');
                $count_winer[] = \app\admin\model\OrderWiner::where('create_time', '>=', $st)->where('create_time', '<=', $et)->count('id');
            }
            $data = [];
            $data['listday'] = $listday;
            $data['deal'] = $count_deal;
            $data['deal_title'] = '币币';
            $data['leverdeal'] = $count_leverdeal;
            $data['leverdeal_title'] = '合约';
            $data['seconds'] = $count_seconds;
            $data['seconds_title'] = '期权';
            $data['coinwin'] = $count_coinwin;
            $data['coinwin_title'] = '理财';
            $data['ieorg'] = $count_ieorg;
            $data['ieorg_title'] = '认购';
            $data['winer'] = $count_winer;
            $data['winer_title'] = '挖矿';
            $data['legend'] = [$data['deal_title'],$data['leverdeal_title'],$data['seconds_title'],$data['coinwin_title'],$data['ieorg_title'],$data['winer_title']];
            return json(['code'=>1,'data'=>$data]);
        }
        return json(['code'=>0]);
    }

    public function findlevel()
    {
        if(request()->isPost()){
            $id = request()->post('id/d','',"int");
            if($id){
                $level = \app\admin\model\MemberUser::where('admin_id','>',0)->where('holder_id',$id)->field('id,username')->select();
                return json(['code'=>1,'data'=>$level]);
            }
            return json(['code'=>0]);
        }
        return json(['code'=>0]);
    }

}