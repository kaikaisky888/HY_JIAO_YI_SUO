<?php
/*
 * @Author: AA
 * @Description: 贷款产品管理
 */

namespace app\admin\controller\loan;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="功能：贷款产品管理")
 */
class Lists extends AdminController
{
    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'asc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new \app\admin\model\LoanLists();
        $this->modelang = new \app\admin\model\LangLists();
        $this->assign('lang_list', $this->lang_list);
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
                ->where($where)
                ->count();

            $list = $this->model
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
            $data['min_amount'] = $post['min_amount'];
            $data['max_amount'] = $post['max_amount'];
            $data['interest_rate'] = $post['interest_rate'];
            $data['repayment_period'] = $post['repayment_period'];
            $data['status'] = isset($post['status']) ? $post['status'] : 1;
            $data['sort'] = $post['sort'];

            if (bccomp($data['min_amount'], '0', 4) <= 0) {
                $this->error('最小贷款金额必须大于0');
            }
            if (bccomp($data['max_amount'], $data['min_amount'], 4) < 0) {
                $this->error('最大贷款金额不能小于最小贷款金额');
            }
            if (bccomp($data['interest_rate'], '0', 4) < 0) {
                $this->error('年化利率不能小于0');
            }
            if ((int)$data['repayment_period'] <= 0) {
                $this->error('还款周期必须大于0');
            }

            foreach ($this->lang_list as $k => $v) {

                if ($v == sysconfig('base', 'base_lang')) {
                    if (empty($title[$v])) {
                        $this->error($v . '标题不能为空');
                    }
                    if (empty($content[$v])) {
                        $this->error($v . '内容描述不能为空');
                    }


                    $data['lang'] = $v;
                    $data['name'] = $title[$v];
                    $data['logo'] = isset($logo[$v]) ? $logo[$v] : '';
                    $data['description'] = $content[$v];
                }
            }

            try {
                $save = $this->model->save($data);
                $lastId = $this->model->id;

                if ($lastId) {
                    $langdata = [];
                    foreach ($this->lang_list as $k => $v) {
                        $langdata[] = [
                            'item'    => 'loan',
                            'item_id' => $lastId,
                            'lang'    => $v,
                            'title'   => $title[$v],
                            'logo'    => isset($logo[$v]) ? $logo[$v] : '',
                            'content' => $content[$v],
                            'remark'  => isset($remark[$v]) ? $remark[$v] : '',
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
        foreach ($this->lang_list as $k => $v) {
            if ($langinfo = $this->modelang->where('item', 'loan')->where('item_id', $row['id'])->where('lang', $v)->find()) {
                $langcon[$v] = $langinfo->toArray();
            } else {
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
            $data['min_amount'] = $post['min_amount'];
            $data['max_amount'] = $post['max_amount'];
            $data['interest_rate'] = $post['interest_rate'];
            $data['repayment_period'] = $post['repayment_period'];
            $data['status'] = isset($post['status']) ? $post['status'] : 1;
            $data['sort'] = $post['sort'];

            if (bccomp($data['min_amount'], '0', 4) <= 0) {
                $this->error('最小贷款金额必须大于0');
            }
            if (bccomp($data['max_amount'], $data['min_amount'], 4) < 0) {
                $this->error('最大贷款金额不能小于最小贷款金额');
            }
            if (bccomp($data['interest_rate'], '0', 4) < 0) {
                $this->error('年化利率不能小于0');
            }
            if ((int)$data['repayment_period'] <= 0) {
                $this->error('还款周期必须大于0');
            }

            foreach ($this->lang_list as $k => $v) {


                if ($v == sysconfig('base', 'base_lang')) {
                    if (empty($title[$v])) {
                        $this->error($v . '标题不能为空');
                    }
                    if (empty($content[$v])) {
                        $this->error($v . '内容描述不能为空');
                    }
                    $data['lang'] = $v;
                    $data['name'] = $title[$v];
                    $data['logo'] = isset($logo[$v]) ? $logo[$v] : '';
                    $data['description'] = $content[$v];
                }
            }

            try {
                $this->modelang->where('item', 'loan')->where('item_id', $id)->delete();

                $save = $this->model->update($data, ['id' => $id]);

                $langdata = [];
                foreach ($this->lang_list as $k => $v) {
                    $langdata[] = [
                        'item'    => 'loan',
                        'item_id' => $id,
                        'lang'    => $v,
                        'title'   => $title[$v],
                        'logo'    => isset($logo[$v]) ? $logo[$v] : '',
                        'content' => $content[$v],
                        'remark'  => isset($remark[$v]) ? $remark[$v] : '',
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

            $loanOrderCount = \app\admin\model\LoanOrder::where('loan_id', $id)->count();
            if ($loanOrderCount > 0) {
                $this->error('该贷款产品已有申请记录，无法删除');
            }

            try {
                $save = $row->delete();
                if ($save) {
                    $this->modelang->where('item', 'loan')->where('item_id', $id)->delete();
                }
            } catch (\Exception $e) {
                $this->error('删除失败');
            }

            $save ? $this->success('删除成功') : $this->error('删除失败');
        }
    }
}