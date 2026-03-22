<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-06-28 14:41:28
 * @LastEditTime: 2026-03-13
 * @Description: Forward, no stop
 */
namespace app\mobile\controller;

use app\common\controller\MobileController;
use think\facade\Log;

class Coinwin extends MobileController
{
    /**
     * 理财产品语言兜底顺序
     * 兼容 zh-cn / zh-ch 两种历史写法
     */
    protected function getCoinwinLangFallbacks()
    {
        return array_values(array_unique(array_filter([
            $this->lang,
            'zh-cn',
            'zh-ch',
            'hk-cn',
            'en-us',
        ])));
    }

    /**
     * 获取理财产品多语言内容
     */
    protected function getGoodLangInfo($goodId)
    {
        $langModel = new \app\admin\model\LangLists();

        foreach ($this->getCoinwinLangFallbacks() as $lang) {
            $langInfo = $langModel
                ->where('item', 'good')
                ->where('item_id', $goodId)
                ->where('lang', $lang)
                ->find();

            if ($langInfo) {
                return $langInfo->toArray();
            }
        }

        return null;
    }

    /**
     * 给理财产品补展示字段
     */
    protected function fillGoodShowFields(array $good)
    {
        $langInfo = $this->getGoodLangInfo($good['id']);

        if ($langInfo) {
            $good['show_title'] = $langInfo['title'] ?? $good['title'];
            $good['show_logo'] = $langInfo['logo'] ?? $good['logo'];
            $good['show_content'] = $langInfo['content'] ?? $good['content'];
            $good['show_remark'] = $langInfo['remark'] ?? $good['remark'];
        } else {
            $good['show_title'] = $good['title'];
            $good['show_logo'] = $good['logo'];
            $good['show_content'] = $good['content'];
            $good['show_remark'] = $good['remark'];
        }

        return $good;
    }

    /**
     * 给理财产品补收益展示字段
     * annual_rate 有值时，前台优先显示配置值
     * 没值时，兼容旧逻辑自动计算年化
     */
    protected function fillGoodRateTexts(array $good)
    {
        $playTime = floatval($good['play_time'] ?? 0);
        $playRate = floatval($good['play_rate'] ?? 0);

        if ($playTime > 0) {
            $dayRate = bcmul(bcdiv((string)$playRate, (string)$playTime, 8), '100', 4);
            $yearRate = bcmul((string)$dayRate, '365', 4);
        } else {
            $dayRate = '0.0000';
            $yearRate = '0.0000';
        }

        $good['day_rate_text'] = number_format((float)$dayRate, 2, '.', '');
        $good['year_rate_text'] = number_format((float)$yearRate, 2, '.', '');
        $good['play_time_text'] = floatVal($good['play_time'] ?? 0);
        $good['play_price_text'] = floatVal($good['play_price'] ?? 0);
        $good['max_price_text'] = floatVal($good['max_price'] ?? 0);

        $annualRate = isset($good['annual_rate']) ? trim((string)$good['annual_rate']) : '';
        $good['annual_rate_text'] = $annualRate !== '' ? $annualRate : ($good['year_rate_text'] . '%');

        return $good;
    }

    /**
     * 根据 good_id 获取当前语言标题
     */
    protected function getGoodShowTitle($goodId, $default = '--')
    {
        $good = \app\admin\model\GoodLists::where('id', $goodId)
            ->field('id,title,logo,content,remark')
            ->find();

        if (!$good) {
            return $default;
        }

        $good = $this->fillGoodShowFields($good->toArray());

        return !empty($good['show_title']) ? $good['show_title'] : $default;
    }

    /**
     * 理财明细 remark 按当前语言显示产品名
     */
    protected function getWalletLogRemarkText($log)
    {
        if ($log instanceof \think\Model) {
            $log = $log->toArray();
        }

        if (!empty($log['order_id'])) {
            $order = \app\admin\model\OrderGood::where('id', $log['order_id'])
                ->field('id,good_id')
                ->find();

            if ($order && !empty($order['good_id'])) {
                return $this->getGoodShowTitle($order['good_id'], $log['remark'] ?: '--');
            }
        }

        return !empty($log['remark']) ? $log['remark'] : '--';
    }

    public function index()
    {
        $goodModel = new \app\admin\model\GoodLists();

        $goods = $goodModel
            ->where('status', 1)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if (!empty($goods)) {
            foreach ($goods as $k => $v) {
                $goods[$k] = $this->fillGoodShowFields($v);
                $goods[$k] = $this->fillGoodRateTexts($goods[$k]);
            }
        }

        $this->assign('goods', $goods);

        $web_name = lang('coinwin.title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'coinwin',
        ]);

        return $this->fetch();
    }

    public function lists()
    {
        $product_id = request()->get('id/d', 0);

        $goods = \app\admin\model\GoodLists::where('status', 1)
            ->where('product_id', $product_id)
            ->order('sort', 'desc')
            ->select()
            ->toArray();

        if ($goods) {
            foreach ($goods as $k => $v) {
                $goods[$k] = $this->fillGoodShowFields($v);
                $goods[$k] = $this->fillGoodRateTexts($goods[$k]);
                $goods[$k]['info'] = [
                    'title'   => $goods[$k]['show_title'],
                    'logo'    => $goods[$k]['show_logo'],
                    'content' => $goods[$k]['show_content'],
                    'remark'  => $goods[$k]['show_remark'],
                ];
            }
        }

        $this->assign('goods', $goods);

        $productBase = \app\admin\model\ProductLists::where('id', $product_id)
            ->field('id,title')
            ->find();

        if (!$productBase) {
            return $this->error(lang('public.do_fail'));
        }

        $info = [];
        $info['money'] = \app\admin\model\MemberWallet::where('product_id', $productBase['id'])
            ->where('uid', $this->memberInfo['id'])
            ->value('up_money');

        $info['rate_account'] = \app\admin\model\OrderGood::where('product_id', $productBase['id'])
            ->where('uid', $this->memberInfo['id'])
            ->sum('rate_account');

        $info['buy_account'] = \app\admin\model\OrderGood::where('product_id', $productBase['id'])
            ->where('uid', $this->memberInfo['id'])
            ->where('status', 1)
            ->sum('buy_account');

        $this->assign('info', $info);
        $this->assign([
            'product_id' => $product_id,
            'coin_title' => $productBase['title']
        ]);

        $web_name = lang('coinwin.title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'coinwin'
        ]);

        return $this->fetch();
    }

    public function dobuy()
    {
        if (!request()->isPost()) {
            Log::warning(
                '理财申购失败：非POST请求'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | ip=' . request()->ip()
            );
            return $this->error(lang('public.do_fail'));
        }

        $good_id = request()->post('good_id', '0', 'int');
        $buy_account = request()->post('buy_account', '0', 'floatVal');

        if ($buy_account <= 0 || $good_id <= 0) {
            Log::warning(
                '理财申购失败：参数异常'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | buy_account=' . $buy_account
                . ' | post=' . json_encode(request()->post(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            return $this->error(lang('coinwin.check_buy_number'));
        }

        $check = request()->checkToken('__token__');
        if (false === $check) {
            Log::warning(
                '理财申购失败：token校验失败'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | buy_account=' . $buy_account
            );
            return $this->error(lang('public.do_fail'));
        }

        $this->model = new \app\admin\model\GoodLists();
        $this->modelwallet = new \app\admin\model\MemberWallet();
        $this->morder = new \app\admin\model\OrderGood();
        $this->modellog = new \app\admin\model\MemberWalletLog();

        $good = $this->model->where('id', $good_id)->find();
        if (!$good) {
            Log::warning(
                '理财申购失败：理财产品不存在'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
            );
            return $this->error(lang('public.do_fail'));
        }

        $productBase = \app\admin\model\ProductLists::where('id', $good['product_id'])
            ->field('id,title')
            ->find();

        if (!$productBase) {
            Log::warning(
                '理财申购失败：产品币种不存在'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | product_id=' . $good['product_id']
            );
            return $this->error(lang('public.do_fail'));
        }

        $user_base_wallet = $this->modelwallet
            ->where('product_id', $productBase['id'])
            ->where('uid', $this->memberInfo['id'])
            ->field('id,up_money')
            ->find();

        if (!$user_base_wallet) {
            Log::warning(
                '理财申购失败：用户钱包不存在'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | product_id=' . $productBase['id']
            );
            return $this->error(lang('public.do_fail'));
        }

        if ($buy_account > $user_base_wallet['up_money']) {
            Log::warning(
                '理财申购失败：余额不足'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | buy_account=' . $buy_account
                . ' | wallet_money=' . $user_base_wallet['up_money']
            );
            return $this->error(fox_all_replace(lang('coinwin.check_buy_money'), floatVal($user_base_wallet['up_money'])));
        }

        if ($good['max_price'] > 0 && $buy_account > $good['max_price']) {
            Log::warning(
                '理财申购失败：超过最大限额'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | buy_account=' . $buy_account
                . ' | max_price=' . $good['max_price']
            );
            return $this->error(fox_all_replace(lang('coinwin.check_max_price'), floatVal($good['max_price'])));
        }

        if ($buy_account < $good['play_price']) {
            Log::warning(
                '理财申购失败：低于最小限额'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | buy_account=' . $buy_account
                . ' | play_price=' . $good['play_price']
            );
            return $this->error(fox_all_replace(lang('coinwin.check_min_price'), floatVal($good['play_price'])));
        }

        if ($good['can_buy'] > 0) {
            $count = $this->morder
                ->where('uid', $this->memberInfo['id'])
                ->where('good_id', $good_id)
                ->where('product_id', $good['product_id'])
                ->where('lock', '>', 0)
                ->count('id');

            if ($count >= $good['can_buy']) {
                Log::warning(
                    '理财申购失败：超过限购次数'
                    . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                    . ' | good_id=' . $good_id
                    . ' | count=' . $count
                    . ' | can_buy=' . $good['can_buy']
                );
                return $this->error(lang('coinwin.can_buy_num', ['num' => $good['can_buy']]));
            }
        }

        $t = strtotime(date("Y-m-d H:i:s", strtotime("+1 day")));

        $indata = [];
        $indata['good_id'] = $good_id;
        $indata['product_id'] = $good['product_id'];
        $indata['uid'] = $this->memberInfo['id'];
        $indata['buy_account'] = $buy_account;
        $indata['time'] = $good['play_time'];
        $indata['rate'] = $good['play_rate'];
        $indata['lock'] = $indata['time'];
        $indata['type'] = 0;
        $indata['status'] = 1;
        $indata['lock_time'] = $t;

        $lastId = 0;

        \think\facade\Db::startTrans();
        try {
            $save = $this->morder->save($indata);
            if (!$save) {
                Log::error(
                    '理财申购失败：保存订单失败'
                    . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                    . ' | indata=' . json_encode($indata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
                \think\facade\Db::rollback();
                return $this->error(lang('public.do_fail'));
            }

            $lastId = $this->morder->id;
            $now_up_money = bc_sub($user_base_wallet['up_money'], $indata['buy_account']);

            $prowallet = $this->modelwallet
                ->where('product_id', $productBase['id'])
                ->where('uid', $this->memberInfo['id'])
                ->update(['up_money' => $now_up_money]);

            if ($prowallet === false) {
                Log::error(
                    '理财申购失败：扣减钱包失败'
                    . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                    . ' | wallet_id=' . $user_base_wallet['id']
                    . ' | before=' . $user_base_wallet['up_money']
                    . ' | after=' . $now_up_money
                    . ' | order_id=' . $lastId
                );
                \think\facade\Db::rollback();
                return $this->error(lang('public.do_fail'));
            }

            $logdata = [];
            $logdata['account'] = $indata['buy_account'];
            $logdata['wallet_id'] = $user_base_wallet['id'];
            $logdata['product_id'] = $productBase['id'];
            $logdata['uid'] = $this->memberInfo['id'];
            $logdata['is_test'] = session('member.is_test');
            $logdata['before'] = $user_base_wallet['up_money'];
            $logdata['after'] = $now_up_money;
            $logdata['account_sxf'] = 0;
            $logdata['all_account'] = bc_sub($logdata['account'], $logdata['account_sxf']);
            $logdata['type'] = 7;
            $logdata['title'] = $productBase['title'];
            $logdata['remark'] = $good['title'];
            $logdata['order_type'] = 1;
            $logdata['order_id'] = $lastId;

            $inlog = $this->modellog->save($logdata);
            if (!$inlog) {
                Log::error(
                    '理财申购失败：写钱包日志失败'
                    . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                    . ' | logdata=' . json_encode($logdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
                \think\facade\Db::rollback();
                return $this->error(lang('public.do_fail'));
            }

            \think\facade\Db::commit();

        } catch (\Throwable $e) {
            \think\facade\Db::rollback();

            Log::error(
                '理财申购异常'
                . ' | uid=' . ($this->memberInfo['id'] ?? 0)
                . ' | good_id=' . $good_id
                . ' | buy_account=' . $buy_account
                . ' | message=' . $e->getMessage()
                . ' | file=' . $e->getFile()
                . ' | line=' . $e->getLine()
                . ' | post=' . json_encode(request()->post(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . ' | trace=' . $e->getTraceAsString()
            );

            return $this->error(lang('public.do_fail'));
        }

        Log::info(
            '理财申购成功'
            . ' | uid=' . ($this->memberInfo['id'] ?? 0)
            . ' | good_id=' . $good_id
            . ' | order_id=' . $lastId
            . ' | buy_account=' . $buy_account
            . ' | product_id=' . $good['product_id']
        );

        $url = (string)url('coinwin/index');
        return $this->success(lang('coinwin.buy_account_ok'), [], $url);
    }

    public function detail()
    {
        $good_id = request()->get('id/d', 0);
        if (empty($good_id)) {
            return $this->error(lang('public.do_fail'));
        }

        $goodModel = new \app\admin\model\GoodLists();

        $good = $goodModel->where('status', 1)->where('id', $good_id)->find();
        if (empty($good)) {
            return $this->error(lang('public.do_fail'));
        }

        $good = $this->fillGoodShowFields($good->toArray());
        $good = $this->fillGoodRateTexts($good);

        $productBase = \app\admin\model\ProductLists::where('id', $good['product_id'])
            ->field('id,title')
            ->find();

        if (!$productBase) {
            return $this->error(lang('public.do_fail'));
        }

        $walletMoney = \app\admin\model\MemberWallet::where('product_id', $productBase['id'])
            ->where('uid', $this->memberInfo['id'])
            ->value('up_money');

        $walletMoney = $walletMoney ? $walletMoney : 0;
        $good['wallet_money_text'] = number_format((float)$walletMoney, 4, '.', '');

        $this->assign('good', $good);
        $this->assign('product_id', $productBase['id']);
        $this->assign('coin_title', $productBase['title']);

        $web_name = lang('coinwin.title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'coinwin',
        ]);

        return $this->fetch();
    }

    public function lista()
    {
        if (request()->isPost()) {
            $post = $this->request->post(null, '', 'trim');
            $page = isset($post['page']) ? intval($post['page']) : 1;
            $product_id = isset($post['product_id']) ? intval($post['product_id']) : 0;
            $limit = 5;

            $this->m_order = new \app\admin\model\OrderGood();

            $query = $this->m_order
                ->where('uid', $this->memberInfo['id'])
                ->where('status', 1);

            if ($product_id > 0) {
                $query->where('product_id', $product_id);
            }

            $list = $query
                ->page($page, $limit)
                ->order('create_time', 'desc')
                ->select();

            $countQuery = $this->m_order
                ->where('uid', $this->memberInfo['id'])
                ->where('status', 1);

            if ($product_id > 0) {
                $countQuery->where('product_id', $product_id);
            }

            $count = $countQuery->count('id');

            $can_win_today = 0;
            if ($list) {
                foreach ($list as $k => $v) {
                    $list[$k]['title'] = $this->getGoodShowTitle($v['good_id'], '--');

                    if ($v['lock'] > 0) {
                        if ($v['status'] == 1) {
                            $list[$k]['upstatus'] = '<span class="color-green">' . lang('coinwin.status_1') . '</span>';
                        } else if ($v['status'] == 2) {
                            $list[$k]['upstatus'] = '<span class="color-red">' . lang('coinwin.status_2') . '</span>';
                        } else {
                            $list[$k]['upstatus'] = '<span class="color-red">--</span>';
                        }
                    } else {
                        $list[$k]['upstatus'] = '<span class="color-red">' . lang('coinwin.lock_0') . '</span>';
                    }

                    $can_win_today += floatval(bc_mul($v['buy_account'], $v['rate']));
                }
            }

            return json([
                'code' => 1,
                'data' => $list,
                'pages' => ceil($count / $limit),
                'can_win_today' => number_format($can_win_today, 4, '.', '')
            ]);
        }
    }

    public function listb()
    {
        if (request()->isPost()) {
            $post = $this->request->post(null, '', 'trim');
            $page = isset($post['page']) ? intval($post['page']) : 1;
            $product_id = isset($post['product_id']) ? intval($post['product_id']) : 0;
            $limit = 5;

            $this->m_order = new \app\admin\model\OrderGood();

            $query = $this->m_order
                ->where('uid', $this->memberInfo['id'])
                ->where('status', 2);

            if ($product_id > 0) {
                $query->where('product_id', $product_id);
            }

            $list = $query
                ->page($page, $limit)
                ->order('create_time', 'desc')
                ->select();

            $countQuery = $this->m_order
                ->where('uid', $this->memberInfo['id'])
                ->where('status', 2);

            if ($product_id > 0) {
                $countQuery->where('product_id', $product_id);
            }

            $count = $countQuery->count('id');

            $can_win_today = 0;
            if ($list) {
                foreach ($list as $k => $v) {
                    $list[$k]['title'] = $this->getGoodShowTitle($v['good_id'], '--');

                    if ($v['status'] == 1) {
                        $list[$k]['upstatus'] = '<span class="color-green">' . lang('coinwin.status_1') . '</span>';
                    } else if ($v['status'] == 2) {
                        $list[$k]['upstatus'] = '<span class="color-red">' . lang('coinwin.status_2') . '</span>';
                    } else {
                        $list[$k]['upstatus'] = '<span class="color-red">--</span>';
                    }
                }
            }

            return json([
                'code' => 1,
                'data' => $list,
                'pages' => ceil($count / $limit),
                'can_win_today' => number_format($can_win_today, 4, '.', '')
            ]);
        }
    }

    public function listc()
    {
        if (request()->isPost()) {
            $post = $this->request->post(null, '', 'trim');
            $page = isset($post['page']) ? intval($post['page']) : 1;
            $product_id = isset($post['product_id']) ? intval($post['product_id']) : 0;
            $limit = 5;

            $this->m_order = new \app\admin\model\MemberWalletLog();

            $query = $this->m_order
                ->where('uid', $this->memberInfo['id'])
                ->where('type', 7);

            if ($product_id > 0) {
                $query->where('product_id', $product_id);
            }

            $list = $query
                ->page($page, $limit)
                ->order('create_time', 'desc')
                ->select();

            $countQuery = $this->m_order
                ->where('uid', $this->memberInfo['id'])
                ->where('type', 7);

            if ($product_id > 0) {
                $countQuery->where('product_id', $product_id);
            }

            $count = $countQuery->count('id');

            $can_win_today = 0;
            if ($list) {
                foreach ($list as $k => $v) {
                    $list[$k]['remark'] = $this->getWalletLogRemarkText($v);

                    if ($v['order_type'] == 1) {
                        $list[$k]['ordertype'] = '<span class="color-blue">' . lang('coinwin.order_type_1') . '</span>';
                        $list[$k]['allacount'] = '<span class="color-blue">- ' . number_format($v['all_account'], 4, '.', '') . '</span>';
                    } else if ($v['order_type'] == 2) {
                        $list[$k]['ordertype'] = '<span class="color-green">' . lang('coinwin.order_type_2') . '</span>';
                        $list[$k]['allacount'] = '<span class="color-green">+ ' . number_format($v['all_account'], 4, '.', '') . '</span>';
                    } else if ($v['order_type'] == 3) {
                        $list[$k]['ordertype'] = '<span class="color-red">' . lang('coinwin.order_type_3') . '</span>';
                        $list[$k]['allacount'] = '<span class="color-red">+ ' . number_format($v['all_account'], 4, '.', '') . '</span>';
                    } else {
                        $list[$k]['ordertype'] = '<span class="color-red">--</span>';
                        $list[$k]['allacount'] = '<span class="color-red">' . number_format($v['all_account'], 4, '.', '') . '</span>';
                    }
                }
            }

            return json([
                'code' => 1,
                'data' => $list,
                'pages' => ceil($count / $limit),
                'can_win_today' => number_format($can_win_today, 4, '.', '')
            ]);
        }
    }

    public function records()
    {
        $web_name = lang('coinwin.title') . '-' . $this->web_name;
        $this->assign([
            'web_name' => $web_name,
            'topmenu'  => 'coinwin',
        ]);

        return $this->fetch();
    }
}