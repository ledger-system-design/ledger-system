<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Balance;
use App\Models\Cash;
use App\Models\Cert;
use App\Models\Member;
use App\Models\Order;
use App\Models\Setting;
use App\Services\FlowService;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    private function me(Request $request): Member
    {
        return $request->attributes->get('member');
    }

    /* ------------------------------------------------------------------
     | 个人中心
     * ------------------------------------------------------------------ */

    /** Member/usercenter */
    public function usercenter(Request $request)
    {
        $me = $this->me($request);

        $certs = Cert::query()->where('memberid', $me->id)->first();
        $order = [
            Order::query()->where('memberid', $me->id)->where('status', 0)->count(),
            Order::query()->where('memberid', $me->id)->where('status', 1)->count(),
            Order::query()->where('memberid', $me->id)->where('status', 3)->count(),
        ];

        // 累计回款 = 已到账任务的本金 + 收益（客户后台需求里的「收益加本金」）
        $totalReturn = round((float) Order::query()
            ->where('memberid', $me->id)
            ->where('status', 3)
            ->selectRaw('COALESCE(SUM(amount + profit), 0) as s')
            ->value('s'), 2);

        return Api::ok([
            'user' => [
                'id' => $me->id,
                'status' => $me->status,
                'pid' => $me->pid,
                'fid' => $me->fid,
                'unionid' => $me->unionid,
                'username' => $me->maskedUsername(),
                'userreal' => $me->userreal,
                'telephone' => $me->telephone,
                'nickname' => $me->nickname,
                'headimgurl' => $me->headimgurl,
                'sex' => $me->sex,
                'invitecode' => $me->invitecode,
                'balance1' => number_format((float) $me->balance1, 2, '.', ''),
                'balance2' => number_format((float) $me->balance2, 2, '.', ''),
                'cert1' => $certs ? (string) $certs->license : '',
                'cert2' => $me->cert2,
                'addtime' => optional($me->addtime)->format('Y-m-d H:i:s') ?? $me->created_at?->format('Y-m-d H:i:s'),
                'parentname' => $me->gradename ?: 'VIP',
                'iscert' => (int) $me->iscert,
                'is_shop' => (int) $me->is_shop,
            ],
            'income' => [
                // 本人参与任务产生的收益累计（直推收益窗口）
                'bonus1' => round((float) Balance::query()->where('memberid', $me->id)->where('typeid', config('flow.type_order'))->sum('amount'), 2),
                // 直推的人参与进来带来的奖励累计
                'bonus2' => round((float) Balance::query()->where('memberid', $me->id)->where('typeid', config('flow.type_direct'))->sum('amount'), 2),
                'total_return' => $totalReturn,
            ],
            'order' => $order,
            'tools' => Setting::json('member_tools', []),
        ]);
    }

    /** Member/profile —— 资料查看 / 修改 / 改密码 */
    public function profile(Request $request, FlowService $flow)
    {
        $me = $this->me($request);
        $act = (string) $request->input('act');

        if ($act === 'view') {
            return Api::ok([
                'id' => $me->id,
                'username' => $me->username,
                'userreal' => $me->userreal,
                'nickname' => $me->nickname,
                'telephone' => $me->telephone,
                'headimgurl' => $me->headimgurl,
                'sex' => $me->sex,
                'invitecode' => $me->invitecode,
                'balance1' => (float) $me->balance1,
                'balance2' => (float) $me->balance2,
                'addtime' => optional($me->addtime)->format('Y-m-d H:i:s'),
            ]);
        }

        if ($act === 'edit') {
            $data = (array) $request->input('data', []);
            $me->nickname = (string) ($data['nickname'] ?? $me->nickname);
            $me->headimgurl = (string) ($data['headimgurl'] ?? $me->headimgurl);
            if (isset($data['sex'])) {
                $me->sex = (int) $data['sex'];
            }
            $me->save();

            return Api::ok('', '修改成功');
        }

        if ($act === 'setuserpwd') {
            $data = (array) $request->input('data', []);
            $old = (string) ($data['userpwd'] ?? '');
            $new = (string) ($data['userpwd1'] ?? '');
            $new2 = (string) ($data['userpwd2'] ?? '');

            if ($old === '' || md5($old) !== $me->userpwd) {
                return Api::fail('原密码错误');
            }
            if ($new === '') {
                return Api::fail('请输入新密码');
            }
            if ($new !== $new2) {
                return Api::fail('两次输入的密码不一致');
            }

            $me->userpwd = md5($new);
            $me->save();

            return Api::ok('', '修改成功，请重新登陆');
        }

        return Api::noAction();
    }

    /* ------------------------------------------------------------------
     | 资金明细 / 提现
     * ------------------------------------------------------------------ */

    /** Member/balance */
    public function balance(Request $request)
    {
        $me = $this->me($request);
        $act = (string) $request->input('act', 'list');
        if ($act !== 'list') {
            return Api::noAction();
        }

        $type = (string) $request->input('type', 'account1');
        $typeid = $request->input('typeid');

        $query = Balance::query()->where('memberid', $me->id);
        if (in_array($type, ['account1', 'account2'], true)) {
            $query->where('account', $type);
        }
        if ($typeid !== null && $typeid !== '') {
            $query->where('typeid', (int) $typeid);
        }

        $page = $query->orderByDesc('id')->paginate(15, ['*'], 'page', (int) $request->input('page', 1));

        return Api::ok(Api::paginate($page, fn (Balance $b) => [
            'id' => $b->id,
            'memberid' => $b->memberid,
            'amount' => number_format((float) $b->amount, 2, '.', ''),
            'type' => $b->type,
            'typeid' => $b->typeid,
            'prebalance' => number_format((float) $b->prebalance, 2, '.', ''),
            'balance' => number_format((float) $b->balance, 2, '.', ''),
            'remark' => $b->remark,
            'remark1' => $b->remark1,
            'calcmonth' => $b->calcmonth,
            'calcdate' => $b->calcdate,
            'addtime' => optional($b->addtime)->format('Y-m-d H:i:s'),
            'username' => $me->username,
            'userreal' => $me->userreal,
        ]));
    }

    /** Member/cash —— 提现申请与提现记录 */
    public function cash(Request $request, FlowService $flow)
    {
        $me = $this->me($request);
        $act = (string) $request->input('act');

        if ($act === 'add') {
            $data = (array) $request->input('data', []);
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $account = (string) ($data['account'] ?? 'account1');
            $type = (int) ($data['type'] ?? 0);

            if ($amount <= 0) {
                return Api::fail('请输入提现金额');
            }
            if ($amount > (float) $me->balance1) {
                return Api::fail('可提现余额不足');
            }

            DB::transaction(function () use ($me, $amount, $account, $type, $flow) {
                $flow->credit($me, $amount, (int) config('flow.type_cash'), '', '提现申请', 0, $account);
                Cash::create([
                    'memberid' => $me->id,
                    'amount' => $amount,
                    'account' => $account,
                    'type' => $type,
                    'status' => 0,
                    'remark' => '提现申请',
                    'addtime' => now(),
                ]);
            });

            return Api::ok('', '提交成功');
        }

        if ($act === 'list') {
            $page = Cash::query()->where('memberid', $me->id)
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'page', (int) $request->input('page', 1));

            return Api::ok(Api::paginate($page, fn (Cash $c) => [
                'id' => $c->id,
                'memberid' => $c->memberid,
                'amount' => number_format((float) $c->amount, 2, '.', ''),
                'type' => $c->status === 0 ? 0 : 1,
                'status' => $c->status,
                'account' => $c->account,
                'remark' => $c->remark,
                'remark1' => '提现' . ($c->status === 0 ? '（审核中）' : ($c->status === 1 ? '（已到账）' : '（已驳回）')),
                'addtime' => optional($c->addtime)->format('Y-m-d H:i:s'),
                'username' => $me->username,
                'userreal' => $me->userreal,
            ]));
        }

        return Api::noAction();
    }

    /** Member/shopincome —— 收益统计 */
    public function shopincome(Request $request)
    {
        $me = $this->me($request);
        $act = (string) $request->input('act');

        if ($act === 'statistics') {
            $type = (string) $request->input('type', '1');
            $typeid = $type === '2' ? config('flow.type_direct') : config('flow.type_order');

            return Api::ok([
                'total' => round((float) Balance::query()->where('memberid', $me->id)->where('typeid', $typeid)->sum('amount'), 2),
                'today' => round((float) Balance::query()->where('memberid', $me->id)->where('typeid', $typeid)->where('calcdate', (int) date('Ymd'))->sum('amount'), 2),
                'balance1' => round((float) $me->balance1, 2),
                'withdrawed' => round((float) $me->withdrawed, 2),
                'pending' => round((float) Balance::query()->where('memberid', $me->id)->whereIn('typeid', [config('flow.type_order'), config('flow.type_direct')])->sum('amount'), 2),
            ]);
        }

        return Api::noAction();
    }

    /* ------------------------------------------------------------------
     | 采购订单（核心业务）
     * ------------------------------------------------------------------ */

    /** Member/order1 */
    public function order1(Request $request, FlowService $flow)
    {
        $me = $this->me($request);
        $act = (string) $request->input('act');

        // 到期的订单由系统（店长审核）自动确认并结算收益
        $this->autoAdvance($flow);

        if ($act === 'list') {
            $status = $request->input('status');

            // 采购任务都是「针对某个用户」发布的，会员只能看到分配给自己的任务
            $query = Order::query()->where('memberid', $me->id);

            if ($status !== null && $status !== '') {
                $query->where('status', (int) $status);
            }

            $page = $query->orderByDesc('id')->paginate(10, ['*'], 'page', (int) $request->input('page', 1));

            return Api::ok(Api::paginate($page, function (Order $o) use ($me) {
                $seller = Member::find($o->sellerid);
                $buyer = Member::find($o->memberid);

                return [
                    'id' => $o->id,
                    'orderno' => $o->orderno,
                    'name' => $o->name,
                    'indexpic' => $o->indexpic,
                    'amount' => number_format((float) $o->amount, 2, '.', ''),
                    'status' => (int) $o->status,
                    'status_text' => $o->statusText(),
                    'sellerreal' => $seller->userreal ?? '',
                    // 手机号脱敏：只露两头，防止会员之间互相骚扰（后台管理员可见完整号码）
                    'sellername' => $seller ? $seller->maskedUsername() : '',
                    'userreal' => $buyer->userreal ?? '',
                    'username' => $buyer ? $buyer->maskedUsername() : '',
                    'is_seller' => (int) $o->sellerid === (int) $me->id,
                    'addtime' => optional($o->addtime)->format('Y-m-d H:i:s'),
                ];
            }));
        }

        if ($act === 'view') {
            $data = (array) $request->input('data', []);
            $orderno = (string) ($data['orderno'] ?? '');
            $order = Order::query()->where('orderno', $orderno)->first();

            if (! $order) {
                return Api::fail('订单不存在');
            }
            if ((int) $order->memberid !== (int) $me->id && (int) $order->sellerid !== (int) $me->id) {
                return Api::fail('无权查看该订单');
            }

            $buyer = Member::find($order->memberid);
            $seller = Member::find($order->sellerid);

            return Api::ok([
                'id' => $order->id,
                'orderno' => $order->orderno,
                'title' => $order->name,
                'name' => $order->name,
                'indexpic' => $order->indexpic,
                'amount' => number_format((float) $order->amount, 2, '.', ''),
                'status' => (int) $order->status,
                'status_text' => $order->statusText(),
                'userreal' => $buyer->userreal ?? '',
                // 手机号脱敏：会员端只能看到两头号码，中间用星号代替
                'username' => $buyer ? $buyer->maskedUsername() : '',
                'sellerreal' => $seller->userreal ?? '',
                'sellername' => $seller ? $seller->maskedUsername() : '',
                'payphoto' => $order->payphoto,
                'addtime' => optional($order->addtime)->format('Y-m-d H:i:s'),
                'paytime' => optional($order->paytime)->format('Y-m-d H:i:s'),
                'paylist' => $this->payList($order),
            ]);
        }

        if ($act === 'pay') {
            $data = (array) $request->input('data', []);
            $orderno = (string) ($data['orderno'] ?? '');
            $order = Order::query()->where('orderno', $orderno)->first();

            if (! $order) {
                return Api::fail('订单不存在');
            }
            if ((int) $order->memberid !== (int) $me->id) {
                return Api::fail('只有买家才能确认支付');
            }
            if ((int) $order->status !== 0) {
                return Api::fail('该订单已支付');
            }

            $order->payphoto = (string) ($data['payphoto'] ?? '');
            $order->paytype = (string) ($data['paytype'] ?? '');
            $order->payname = (string) ($data['payname'] ?? '');
            $order->status = 1;
            $order->paytime = now();
            $order->save();

            return Api::ok('', '确认支付成功');
        }

        if ($act === 'confirm') {
            $data = (array) $request->input('data', []);
            $orderno = (string) ($data['orderno'] ?? '');
            $order = Order::query()->where('orderno', $orderno)->first();

            if (! $order) {
                return Api::fail('订单不存在');
            }
            if ((int) $order->sellerid !== (int) $me->id) {
                return Api::fail('只有卖家才能确认收款');
            }
            if (! $flow->confirmOrder($order)) {
                return Api::fail('该订单当前状态不可确认');
            }

            return Api::ok('', '确认收款成功');
        }

        return Api::noAction();
    }

    /**
     * 自动推进订单状态：
     *  - 已支付满 30 秒的订单 → 待确认 => 已确认
     *  - 已确认满 30 秒的订单 → 收益入账 => 已完成（4% 溢价分配）
     *
     * 现在已有管理后台，默认关闭自动结算，改由管理员在「采购记录」里手动确认到账；
     * 如需演示自动流转，可在后台「系统设置」中打开 auto_settle 开关。
     */
    private function autoAdvance(FlowService $flow): void
    {
        if ((int) \App\Models\Setting::get('auto_settle', 0) !== 1) {
            return;
        }

        $pending = Order::query()->where('status', 1)
            ->where('paytime', '<=', now()->subSeconds(30))
            ->limit(100)->get();

        foreach ($pending as $order) {
            $flow->confirmOrder($order);
        }

        $confirmed = Order::query()->where('status', 2)
            ->where('confirmtime', '<=', now()->subSeconds(30))
            ->limit(100)->get();

        foreach ($confirmed as $order) {
            $flow->settleOrder($order);
        }
    }

    /** 卖家（收款人）的收款方式列表 */
    private function payList(Order $order): array
    {
        $seller = Member::find($order->sellerid);
        $cert = $seller ? Cert::query()->where('memberid', $seller->id)->first() : null;
        $payinfo = $cert && is_array($cert->payinfo) ? $cert->payinfo : Cert::defaultPayinfo();

        $map = [
            'bank' => '银行卡',
            'wechat' => '微信',
            'alipay' => '支付宝',
            'url' => '收款码',
        ];

        $list = [];
        foreach ($payinfo as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = (string) ($item['type'] ?? 'url');
            $hasValue = false;
            if ($type === 'bank') {
                $hasValue = ! empty($item['cardno']) || ! empty($item['carduser']);
            } elseif ($type === 'alipay') {
                $hasValue = ! empty($item['account']) || ! empty($item['image']);
            } else {
                $hasValue = ! empty($item['image']) || ! empty($item['url']);
            }
            if (! $hasValue) {
                continue;
            }

            $list[] = [
                'index' => $idx,
                'type' => $type,
                'name' => (string) ($item['name'] ?? ($map[$type] ?? '收款方式')),
                'url' => (string) ($item['url'] ?? ($item['image'] ?? '')),
                'account' => (string) ($item['account'] ?? ''),
                'image' => (string) ($item['image'] ?? ''),
                'cardno' => (string) ($item['cardno'] ?? ''),
                'carduser' => (string) ($item['carduser'] ?? ''),
                'cardbank' => (string) ($item['cardbank'] ?? ''),
            ];
        }

        // 兜底：卖家未配置收款方式时，给出线下沟通提示
        if (! $list) {
            $list[] = [
                'index' => 0,
                'type' => 'url',
                'name' => '收款说明',
                'url' => '卖家尚未配置收款方式，请联系店长',
                'account' => '',
                'image' => '',
                'cardno' => '',
                'carduser' => '',
                'cardbank' => '',
            ];
        }

        // 需求要求采购清单详情优先展示收款人的支付宝 / 微信收款二维码
        $weight = ['alipay' => 0, 'wechat' => 1, 'url' => 2, 'bank' => 3];
        usort($list, function ($a, $b) use ($weight) {
            return ($weight[$a['type']] ?? 9) <=> ($weight[$b['type']] ?? 9);
        });

        return $list;
    }

    /* ------------------------------------------------------------------
     | 店铺认证
     * ------------------------------------------------------------------ */

    /** Member/cert */
    public function cert(Request $request)
    {
        $me = $this->me($request);
        $act = (string) $request->input('act');

        $cert = Cert::query()->firstOrCreate(
            ['memberid' => $me->id],
            ['license' => '', 'shopinfo' => [], 'payinfo' => Cert::defaultPayinfo(), 'iscert' => 0, 'addtime' => now()]
        );

        if ($act === 'view') {
            return Api::ok([
                'id' => $cert->id,
                'iscert' => (int) $cert->iscert,
                'license' => $cert->license,
                'shopinfo' => $cert->shopinfo ?: new \stdClass(),
                'payinfo' => $this->normalizePayinfo($cert->payinfo),
            ]);
        }

        // 客户要求：店铺认证不对普通用户开放，认证资料由管理员在后台录入
        if ($act === 'save' || $act === 'edit') {
            return Api::fail('店铺认证由管理员统一录入，如需认证请联系管理员');
        }

        return Api::noAction();
    }

    /** 保证 payinfo 始终是 5 项固定结构 */
    private function normalizePayinfo($payinfo): array
    {
        $default = Cert::defaultPayinfo();
        if (! is_array($payinfo)) {
            return $default;
        }

        $out = [];
        foreach ($default as $i => $item) {
            $out[$i] = array_merge($item, is_array($payinfo[$i] ?? null) ? $payinfo[$i] : []);
        }

        return $out;
    }

    /* ------------------------------------------------------------------
     | 团队采购
     * ------------------------------------------------------------------ */

    /** Member/children */
    public function children(Request $request)
    {
        $me = $this->me($request);

        $page = Member::query()->where('pid', $me->id)
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'page', (int) $request->input('page', 1));

        $childrenIds = Member::query()->where('pid', $me->id)->pluck('id')->all();
        $teamIds = $this->teamIds($me->id);

        return Api::ok(array_merge(
            Api::paginate($page, function (Member $m) {
                $stat = $this->purchaseStat([$m->id]);

                return [
                    'id' => $m->id,
                    'username' => $m->username,
                    'userreal' => $m->userreal ?: $m->nickname,
                    'telephone' => $m->telephone,
                    'pid' => $m->pid,
                    'fchild' => Member::query()->where('pid', $m->id)->count(),
                    'amount' => $stat['amount'],
                    'num' => $stat['num'],
                    'pidname' => $m->gradename ?: 'VIP',
                    'addtime' => optional($m->addtime)->format('Y-m-d H:i:s'),
                ];
            }),
            [
                'summary' => [
                    'all' => array_merge($this->purchaseStat($teamIds), [
                        'amount0' => $this->purchaseStat([$me->id])['amount'],
                        'num0' => $this->purchaseStat([$me->id])['num'],
                        'amount1' => $this->purchaseStat($childrenIds)['amount'],
                        'num1' => $this->purchaseStat($childrenIds)['num'],
                        'num_team' => count($teamIds),
                        'num_recommend' => count($childrenIds),
                    ]),
                ],
            ]
        ));
    }

    /** 团队成员（自己 + 所有下级） */
    private function teamIds(int $rootId): array
    {
        $ids = [];
        $queue = [$rootId];
        $guard = 0;

        while ($queue && $guard++ < 20) {
            $level = Member::query()->whereIn('pid', $queue)->pluck('id')->all();
            if (! $level) {
                break;
            }
            $ids = array_merge($ids, $level);
            $queue = $level;
        }

        return $ids;
    }

    /** 采购总金额 / 采购数量 */
    private function purchaseStat(array $memberIds): array
    {
        if (! $memberIds) {
            return ['amount' => 0, 'num' => 0];
        }

        $row = Order::query()->whereIn('memberid', $memberIds)
            ->whereIn('status', [1, 2, 3])
            ->selectRaw('COALESCE(SUM(amount),0) as amount, COUNT(*) as num')
            ->first();

        return [
            'amount' => round((float) ($row->amount ?? 0), 2),
            'num' => (int) ($row->num ?? 0),
        ];
    }

    /* ------------------------------------------------------------------
     | 收货地址
     * ------------------------------------------------------------------ */

    /** Member/address */
    public function address(Request $request)
    {
        $me = $this->me($request);
        $act = (string) $request->input('act');
        $data = (array) $request->input('data', []);

        if ($act === 'list') {
            $page = Address::query()->where('memberid', $me->id)
                ->orderByDesc('isdefault')->orderByDesc('id')
                ->paginate(15, ['*'], 'page', (int) $request->input('page', 1));

            return Api::ok(Api::paginate($page, fn (Address $a) => [
                'id' => $a->id,
                'userreal' => $a->userreal,
                'telephone' => $a->telephone,
                'region' => str_replace(',', ', ', (string) $a->region),
                'address' => $a->address,
                'isdefault' => (int) $a->isdefault,
                'addtime' => optional($a->addtime)->format('Y-m-d H:i:s'),
            ]));
        }

        if ($act === 'view') {
            $a = Address::query()->where('memberid', $me->id)->find((int) ($data['id'] ?? 0));
            if (! $a) {
                return Api::fail('地址不存在');
            }

            return Api::ok([
                'id' => $a->id,
                'userreal' => $a->userreal,
                'telephone' => $a->telephone,
                'region' => $a->region,
                'address' => $a->address,
                'isdefault' => (int) $a->isdefault,
            ]);
        }

        if (in_array($act, ['add', 'edit', 'save'], true)) {
            $id = (int) ($data['id'] ?? 0);
            $payload = [
                'userreal' => (string) ($data['userreal'] ?? ''),
                'telephone' => (string) ($data['telephone'] ?? ''),
                'region' => str_replace(', ', ',', (string) ($data['region'] ?? '')),
                'address' => (string) ($data['address'] ?? ''),
                'isdefault' => (int) ($data['isdefault'] ?? 0),
            ];

            if ($payload['userreal'] === '' || $payload['telephone'] === '' || $payload['address'] === '') {
                return Api::fail('请填写完整的地址信息');
            }

            if ($payload['isdefault'] === 1) {
                Address::query()->where('memberid', $me->id)->update(['isdefault' => 0]);
            }

            if ($id > 0) {
                $a = Address::query()->where('memberid', $me->id)->find($id);
                if (! $a) {
                    return Api::fail('地址不存在');
                }
                $a->fill($payload)->save();
            } else {
                $payload['memberid'] = $me->id;
                $payload['addtime'] = now();
                Address::create($payload);
            }

            return Api::ok('', '保存成功');
        }

        if ($act === 'setdefault') {
            $id = (int) ($data['id'] ?? 0);
            Address::query()->where('memberid', $me->id)->update(['isdefault' => 0]);
            Address::query()->where('memberid', $me->id)->where('id', $id)->update(['isdefault' => 1]);

            return Api::ok('', '设置成功');
        }

        if ($act === 'delete') {
            Address::query()->where('memberid', $me->id)->where('id', (int) ($data['id'] ?? 0))->delete();

            return Api::ok('', '删除成功');
        }

        return Api::noAction();
    }
}
