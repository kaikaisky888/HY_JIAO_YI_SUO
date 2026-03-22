<?php
namespace app\admin\controller\member;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="会员：用户认证")
 */
class Advanced extends AdminController
{
    protected $sort = [
        'update_time' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\MemberCard();
    }

    /**
     * @NodeAnotation(title="实名认证列表")
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

            // 只看已申请高级认证的数据
            $where[] = ['advanced_status', '>', 0];

            $count = $this->model
                ->withJoin(['memberUser'], 'LEFT')
                ->where($where)
                ->count();

            $list = $this->model
                ->withJoin(['memberUser'], 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order(['advanced_time' => 'desc', 'id' => 'desc'])
                ->select();

            return json([
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ]);
        }


        return $this->fetch();
    }



    /**
     * @NodeAnotation(title="高级认证审核")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');

        if (!$row['advanced_video']) {
            $this->error('该用户未提交高级认证');
        }

        if ($this->request->isAjax()) {
            $post = $this->request->post();

            $saveData = [
                'advanced_status'  => $post['advanced_status'],
                'advanced_remark'  => $post['advanced_remark'] ?? '',
                'advanced_do_time' => in_array($post['advanced_status'], [2,3]) ? time() : 0,
                'update_time'      => time(),
            ];

            try {
                $save = $row->save($saveData);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }

            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        $this->assign('row', $row);
        return $this->fetch();
    }
}