<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\AdminLog;
use App\Models\Balance;
use App\Models\Cash;
use App\Models\Cert;
use App\Models\Member;
use App\Models\Order;
use App\Services\FlowService;
use App\Support\Inputs;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /** 用户列表：所有用户的详细数据 */
    public function index(Request $request)
    {
        $query = Member::query();

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('username', 'like', "%{$kw}%")
                    ->orWhere('userreal', 'like', "%{$kw}%")
                    ->orWhere('nickname', 'like', "%{$kw}%")
                    ->orWhere('invitecode', 'like', "%{$kw}%")
                    ->orWhere('id', $kw);
            });
        }

        if ($request->filled('pid')) {
            $query->where('pid', (int) $request->input('pid'));
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('iscert') && $request->input('iscert') !== '') {
            $query->where('iscert', (int) $request->input('iscert'));
        }

        if ($request->filled('is_shop') && $request->input('is_shop') !== '') {
            $query->where('is_shop', (int) $request->input('is_shop'));
        }

        $sort = (string) $request->input('sort', 'id');
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $allowSort = ['id', 'balance1', 'balance2', 'bonus1', 'bonus2', 'addtime', 'logintimes'];
        if (! in_array($sort, $allowSort, true)) {
            $sort = 'id';
        }

        $members = $query->orderBy($sort, $dir)->paginate(20)->withQueryString();

        // 每个用户的关键统计
        $stat = [];
        foreach ($members as $m) {
            $stat[$m->id] = [
                'team' => $this->teamIds($m->id) ? count($this->teamIds($m->id)) : 0,
                'children' => Member::where('pid', $m->id)->count(),
                'orders' => Order::where('memberid', $m->id)->count(),
                'orders_arrived' => Order::where('memberid', $m->id)->where('status', 3)->count(),
                'flow' => (float) Order::where('memberid', $m->id)->whereIn('status', [1, 2, 3])->sum('amount'),
            ];
        }

        $parents = Member::query()->orderByDesc('id')->limit(200)->get(['id', 'userreal', 'username']);

        return view('admin.members.index', compact('members', 'stat', 'parents'));
    }

    /** 新增用户表单 */
    public function create()
    {
        $members = Member::query()->orderByDesc('id')->limit(500)->get(['id', 'userreal', 'username', 'invitecode']);

        return view('admin.members.create', compact('members'));
    }

    /** 新增用户 */
    public function store(Request $request, FlowService $flow)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:32'],
            'userreal' => ['required', 'string', 'max:32'],
            'userpwd' => ['required', 'string', 'min:6', 'max:32'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'nickname' => ['nullable', 'string', 'max:64'],
            'headimgurl' => ['nullable', 'string', 'max:255'],
            'invitecode' => ['nullable', 'string', 'max:16'],
            'gradename' => ['nullable', 'string', 'max:32'],
            'rate_percent' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'balance1' => ['nullable', 'numeric'],
            'balance2' => ['nullable', 'numeric'],
            'iscert' => ['nullable', 'integer', 'in:0,1'],
            'is_shop' => ['nullable', 'integer', 'in:0,1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ], [], [
            'username' => '登录账号',
            'userreal' => '真实姓名',
            'userpwd' => '登录密码',
            'invitecode' => '推荐码',
        ]);

        if (! preg_match('/^1[3-9]\d{9}$/', $data['username'])) {
            return back()->withErrors(['username' => '登录账号必须是 11 位手机号'])->withInput();
        }

        if (Member::query()->where('username', $data['username'])->exists()) {
            return back()->withErrors(['username' => '该手机号已存在'])->withInput();
        }

        // 推荐码可选：留空表示新建的是顶级用户；填了就必须是已存在且有效的推荐码
        $invitecode = trim((string) ($data['invitecode'] ?? ''));
        $parent = null;

        if ($invitecode !== '') {
            $parent = Member::query()->where('invitecode', $invitecode)->first();
            if (! $parent) {
                return back()->withErrors(['invitecode' => '推荐码不存在，请确认后重试；留空可创建顶级用户'])->withInput();
            }
        }

        $member = Member::create([
            'pid' => $parent ? $parent->id : 0,
            'fid' => $parent ? ($parent->fid ?: $parent->id) : 0,
            'unionid' => (string) random_int(1000000000, 1999999999),
            'username' => $data['username'],
            'userpwd' => md5($data['userpwd']),
            'userreal' => $data['userreal'],
            'nickname' => ($data['nickname'] ?? '') ?: ($data['userreal'] ?: $data['username']),
            'telephone' => ($data['telephone'] ?? '') ?: $data['username'],
            'headimgurl' => $data['headimgurl'] ?? '',
            'sex' => 1,
            'invitecode' => $flow->makeInviteCode(),
            'gradename' => $data['gradename'] ?: 'VIP',
            'rate' => $request->filled('rate_percent') ? round((float) $data['rate_percent'] / 100, 4) : null,
            'balance1' => (float) ($data['balance1'] ?? 0),
            'balance2' => (float) ($data['balance2'] ?? 0),
            'iscert' => (int) ($data['iscert'] ?? 0),
            'is_shop' => (int) ($data['is_shop'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
            'addtime' => now(),
        ]);

        AdminLog::write('member.store', '新增会员 ' . $member->username, 'member', $member->id, [
            'username' => $member->username,
            'pid' => $member->pid,
        ]);

        return redirect()->route('admin.members.show', $member->id)
            ->with('status', '会员已创建，推荐码：' . $member->invitecode);
    }

    /** 用户详情：全部关联数据 */
    public function show(Request $request, int $id)
    {
        $member = Member::findOrFail($id);

        $tab = (string) $request->input('tab', 'orders');

        $orders = Order::query()->where('memberid', $id)->orderByDesc('id')->paginate(10, ['*'], 'opage');
        $sold = Order::query()->where('sellerid', $id)->orderByDesc('id')->paginate(10, ['*'], 'spage');
        $balances = Balance::query()->where('memberid', $id)->orderByDesc('id')->paginate(15, ['*'], 'bpage');
        $cashes = Cash::query()->where('memberid', $id)->orderByDesc('id')->paginate(10, ['*'], 'cpage');
        $addresses = Address::query()->where('memberid', $id)->orderByDesc('id')->get();
        $cert = Cert::query()->where('memberid', $id)->first();

        $children = Member::query()->where('pid', $id)->orderByDesc('id')->get();
        $parent = $member->pid ? Member::find($member->pid) : null;

        $summary = [
            'flow' => (float) Order::where('memberid', $id)->whereIn('status', [1, 2, 3])->sum('amount'),
            'flow_num' => Order::where('memberid', $id)->whereIn('status', [1, 2, 3])->count(),
            'bonus1' => (float) Balance::where('memberid', $id)->where('typeid', config('flow.type_order'))->sum('amount'),
            'bonus2' => (float) Balance::where('memberid', $id)->where('typeid', config('flow.type_direct'))->sum('amount'),
            'team' => count($this->teamIds($id)),
        ];

        return view('admin.members.show', compact(
            'member', 'tab', 'orders', 'sold', 'balances', 'cashes',
            'addresses', 'cert', 'children', 'parent', 'summary'
        ));
    }

    /** 编辑用户 */
    public function edit(int $id)
    {
        $member = Member::findOrFail($id);
        $parent = $member->pid ? Member::find($member->pid) : null;
        $members = Member::query()->where('id', '!=', $id)->orderByDesc('id')->limit(500)->get(['id', 'userreal', 'username']);

        return view('admin.members.edit', compact('member', 'parent', 'members'));
    }

    public function update(Request $request, int $id)
    {
        $member = Member::findOrFail($id);

        $data = $request->validate([
            'username' => ['required', 'string', 'max:32'],
            'userreal' => ['nullable', 'string', 'max:32'],
            'nickname' => ['nullable', 'string', 'max:64'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'headimgurl' => ['nullable', 'string', 'max:255'],
            'pid' => ['nullable', 'integer'],
            'fid' => ['nullable', 'integer'],
            'invitecode' => ['nullable', 'string', 'max:16'],
            'gradename' => ['nullable', 'string', 'max:32'],
            'rate_percent' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'balance1' => ['nullable', 'numeric'],
            'balance2' => ['nullable', 'numeric'],
            'bonus1' => ['nullable', 'numeric'],
            'bonus2' => ['nullable', 'numeric'],
            'withdrawed' => ['nullable', 'numeric'],
            'iscert' => ['nullable', 'integer'],
            'is_shop' => ['nullable', 'integer'],
            'status' => ['nullable', 'integer'],
            'userpwd' => ['nullable', 'string', 'min:6', 'max:32'],
        ], [], [
            'username' => '登录账号',
            'userreal' => '真实姓名',
            'nickname' => '昵称',
            'pid' => '直推人',
            'userpwd' => '登录密码',
        ]);

        $exists = Member::query()->where('username', $data['username'])->where('id', '!=', $id)->exists();
        if ($exists) {
            return back()->withErrors(['username' => '该手机号已被其它会员使用'])->withInput();
        }

        $before = $member->only(array_keys($data));

        // 留空字段会变成 null，直接写库会触发 NOT NULL 约束（线上表现为 500）
        $data = Inputs::nullToEmpty($data, [
            'userreal', 'nickname', 'telephone', 'headimgurl',
            'invitecode', 'gradename', 'userpwd',
        ]);
        if (($data['userpwd'] ?? '') === '') {
            unset($data['userpwd']);
        }
        // rate_percent 只是表单用的百分比，真正入库的是 rate 字段
        unset($data['rate_percent']);

        $member->fill($data);

        // 专属收益率：后台按百分比录入，留空表示跟随全局
        $member->rate = $request->filled('rate_percent')
            ? round((float) $request->input('rate_percent') / 100, 4)
            : null;

        if (! empty($data['userpwd'])) {
            $member->userpwd = md5($data['userpwd']);
        }
        $member->save();

        \App\Models\AdminLog::write('member.update', '修改会员资料 #' . $member->id, 'member', $member->id, [
            'before' => $before,
            'after' => $member->only(array_keys($data)),
        ]);

        return redirect()->route('admin.members.show', $member->id)->with('status', '会员资料已保存');
    }

    /** 启用 / 禁用 */
    public function toggle(Request $request, int $id)
    {
        $member = Member::findOrFail($id);
        $member->status = (int) $member->status === 1 ? 0 : 1;
        $member->save();

        \App\Models\AdminLog::write('member.toggle', ($member->status ? '启用' : '禁用') . '会员 #' . $member->id, 'member', $member->id);

        return back()->with('status', '会员已' . ($member->status ? '启用' : '禁用'));
    }

    /** 手动调整余额（正数增加 / 负数扣减） */
    public function adjust(Request $request, int $id, FlowService $flow)
    {
        $member = Member::findOrFail($id);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'account' => ['nullable', 'in:account1,account2'],
            'remark' => ['nullable', 'string', 'max:160'],
        ], [], [
            'amount' => '调整金额',
            'remark' => '备注',
        ]);

        $amount = (float) $data['amount'];
        $account = $data['account'] ?? 'account1';
        $remark = $data['remark'] ?: '后台手动调整';

        $flow->credit(
            $member,
            abs($amount),
            (int) ($amount > 0 ? config('flow.type_recharge') : config('flow.type_cash')),
            '',
            $remark,
            $amount > 0 ? 1 : 0,
            $account
        );

        \App\Models\AdminLog::write('member.adjust', '调整会员余额 #' . $member->id, 'member', $member->id, [
            'amount' => $amount,
            'account' => $account,
            'remark' => $remark,
        ]);

        return back()->with('status', '余额已调整：' . ($amount > 0 ? '+' : '') . $amount);
    }

    /** 删除会员及其关联数据 */
    public function destroy(int $id)
    {
        $member = Member::findOrFail($id);

        Order::where('memberid', $id)->orWhere('sellerid', $id)->delete();
        Balance::where('memberid', $id)->delete();
        Cash::where('memberid', $id)->delete();
        Address::where('memberid', $id)->delete();
        Cert::where('memberid', $id)->delete();

        Member::where('pid', $id)->update(['pid' => $member->pid]);

        \App\Models\AdminLog::write('member.destroy', '删除会员 #' . $id . '（' . $member->username . '）', 'member', $id);
        $member->delete();

        return redirect()->route('admin.members.index')->with('status', '会员已删除');
    }

    /**
     * 清空所有用户数据（仅超级管理员）
     *
     * 客户要求：超级管理员可以「删除所有或者单独用户的数据」。
     * 只清业务数据（会员 / 采购 / 资金 / 提现 / 地址 / 认证），管理员账号与站点配置保留。
     */
    public function purge(Request $request)
    {
        $request->validate([
            'confirm_text' => ['required', 'string'],
        ], [
            'confirm_text.required' => '请输入 DEL 确认再提交',
        ], [
            'confirm_text' => '确认口令',
        ]);

        if (strtoupper(trim((string) $request->input('confirm_text'))) !== 'DEL') {
            return back()->withErrors(['confirm_text' => '确认口令不正确，请输入 DEL（三个大写字母）后重试']);
        }

        $counts = [
            'members' => Member::count(),
            'orders' => Order::count(),
            'balances' => Balance::count(),
            'cashes' => Cash::count(),
            'addresses' => Address::count(),
            'certs' => Cert::count(),
        ];

        Order::query()->delete();
        Balance::query()->delete();
        Cash::query()->delete();
        Address::query()->delete();
        Cert::query()->delete();
        Member::query()->delete();

        \App\Models\AdminLog::write('member.purge', '清空所有用户数据', 'member', '', $counts);

        return redirect()->route('admin.members.index')->with(
            'status',
            '已清空用户数据：会员 ' . $counts['members'] . ' 人、采购任务 ' . $counts['orders']
            . ' 条、资金明细 ' . $counts['balances'] . ' 条（管理员账号与站点配置已保留）'
        );
    }

    /** 团队成员 ID 集合（不含自己） */
    public function teamIds(int $rootId): array
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
}
