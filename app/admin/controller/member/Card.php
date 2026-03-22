<?php
namespace app\admin\controller\member;

use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="会员：用户认证")
 */
class Card extends AdminController
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

            $count = $this->model
                ->withJoin(['memberUser'], 'LEFT')
                ->where($where)
                ->count();

            $list = $this->model
                ->withJoin(['memberUser'], 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
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
     * @NodeAnotation(title="实名认证审核")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');

        if ($this->request->isAjax()) {
            $post = $this->request->post();

            $rule = [];
            $this->validate($post, $rule);

            try {
                $save = $row->save([
                    'status'      => $post['status'],
                    'remark'      => $post['remark'] ?? '',
                    'update_time' => time(),
                ]);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }

            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        $this->assign('row', $row);
        return $this->fetch();
    }


}