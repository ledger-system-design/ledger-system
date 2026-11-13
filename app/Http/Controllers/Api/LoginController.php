<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Setting;
use App\Services\FlowService;
use App\Support\Api;
use App\Support\Jwt;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $username = trim((string) $request->input('username'));
        $userpwd = (string) $request->input('userpwd');

        if ($username === '') {
            return Api::fail('请输入账号');
        }
        if ($userpwd === '') {
            return Api::fail('请输入密码');
        }

        $member = Member::query()->where('username', $username)->first();

        if (! $member || $member->userpwd !== md5($userpwd)) {
            return Api::fail('账号或密码错误');
        }

        if ((int) $member->status !== 1) {
            return Api::fail('账号已被禁用，请联系店长');
        }

        $member->logintimes = (int) $member->logintimes + 1;
        $member->save();

        return Api::ok($this->makeToken($member));
    }

    public function register(Request $request, FlowService $flow)
    {
        // 客户要求：新用户统一由管理员在后台代注册，前台不再开放自助注册
        if ((string) Setting::get('register_open', '0') !== '1') {
            return Api::fail('注册已关闭，请联系管理员开通账号');
        }

        $data = $request->input('data');
        if (! is_array($data)) {
            $data = $request->all();
        }

        $username = trim((string) ($data['username'] ?? ''));
        $userreal = trim((string) ($data['userreal'] ?? ''));
        $userpwd = (string) ($data['userpwd'] ?? '');
        $userpwd2 = (string) ($data['userpwd2'] ?? '');
        $invitecode = trim((string) ($data['invitecode'] ?? ''));

        if ($username === '') {
            return Api::fail('注册账号不能为空');
        }
        if (! preg_match('/^1[3-9]\d{9}$/', $username)) {
            return Api::fail('请输入正确的手机号码');
        }
        if ($userreal === '') {
            return Api::fail('请输入姓名');
        }
        if ($userpwd === '') {
            return Api::fail('请输入密码');
        }
        if ($userpwd2 !== '' && $userpwd !== $userpwd2) {
            return Api::fail('两次输入的密码不一致');
        }
        if (Member::query()->where('username', $username)->exists()) {
            return Api::fail('该手机号已注册');
        }

        // 推荐关系：必须有推荐码（邀请码对应第一代 / 直推人）
        if ($invitecode === '') {
            return Api::fail('请填写推荐码');
        }

        $parent = Member::query()->where('invitecode', $invitecode)->first();
        if (! $parent) {
            return Api::fail('推荐码不存在');
        }

        $member = Member::create([
            'pid' => $parent->id,
            'fid' => $parent->fid ?: $parent->id,
            'unionid' => (string) random_int(1000000000, 1999999999),
            'username' => $username,
            'userpwd' => md5($userpwd),
            'userreal' => $userreal,
            // 昵称默认取姓名（没有姓名时退回账号），不再生成 user_xxxxxx 这种占位昵称
            'nickname' => $userreal !== '' ? $userreal : $username,
            'telephone' => $username,
            'sex' => 1,
            'invitecode' => $flow->makeInviteCode(),
            'gradename' => $parent->gradename ?: 'VIP',
            'status' => 1,
            'addtime' => now(),
        ]);

        // 服务费分配：店长 700（直推 200 / 间推 98 按客户要求不再发放）
        $this->distributeServiceFee($member, $flow);

        return Api::ok(['id' => $member->id], '注册成功');
    }

    /**
     * 998 元服务费分配
     *
     * 客户二次需求：可提现余额只体现「直推的人参与进来的奖励收益」，
     * 不再发放直推 200 元 / 间推 98 元（这两笔会污染余额口径），
     * 仅保留店长 700 元分成。
     */
    private function distributeServiceFee(Member $member, FlowService $flow): void
    {
        $direct = $member->pid ? Member::find($member->pid) : null;
        $shopId = $member->fid ?: ($direct->fid ?? 0);
        $shop = $shopId ? Member::find($shopId) : null;

        if ($shop && (int) $shop->id !== (int) ($direct->id ?? 0)) {
            $flow->credit($shop, (float) config('flow.fee_shop'), (int) config('flow.type_fee'), (string) $member->id, '【服务费】店长分成');
        }

        Setting::put('platform_profit', (float) Setting::get('platform_profit', 0));
    }

    private function makeToken(Member $member): array
    {
        $extend = [
            'id' => $member->id,
            'username' => $member->username,
            'userpwd' => $member->userpwd,
            'userreal' => $member->userreal,
            'logintimes' => $member->logintimes,
            'status' => $member->status,
        ];

        return [
            'token_type' => 'Bearer',
            'expires_in' => 720000,
            'access_token' => Jwt::encode(['extend' => $extend], 720000),
            'refresh_token' => Jwt::encode(['extend' => $extend], 604800),
            'id' => $member->id,
        ];
    }
}
