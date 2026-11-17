<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\AdminLog;
use App\Models\Cert;
use App\Models\Member;
use Illuminate\Http\Request;

/**
 * 会员收款方式（店铺认证）管理
 */
class CertController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query();

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('username', 'like', "%{$kw}%")
                    ->orWhere('userreal', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('iscert') && $request->input('iscert') !== '') {
            $query->where('iscert', (int) $request->input('iscert'));
        }

        $members = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $certs = [];
        foreach ($members as $m) {
            $certs[$m->id] = Cert::query()->where('memberid', $m->id)->first();
        }

        return view('admin.certs.index', compact('members', 'certs'));
    }

    public function edit(int $id)
    {
        $member = Member::findOrFail($id);
        $cert = Cert::query()->firstOrCreate(
            ['memberid' => $id],
            ['license' => '', 'shopinfo' => [], 'payinfo' => Cert::defaultPayinfo(), 'iscert' => 0, 'addtime' => now()]
        );

        $payinfo = $cert->payinfo;
        if (! is_array($payinfo) || ! $payinfo) {
            $payinfo = Cert::defaultPayinfo();
        }
        // 保证 5 项结构完整
        $default = Cert::defaultPayinfo();
        $merged = [];
        foreach ($default as $i => $item) {
            $merged[$i] = array_merge($item, is_array($payinfo[$i] ?? null) ? $payinfo[$i] : []);
        }

        return view('admin.certs.edit', [
            'member' => $member,
            'cert' => $cert,
            'payinfo' => $merged,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $member = Member::findOrFail($id);

        $cert = Cert::query()->firstOrCreate(
            ['memberid' => $id],
            ['license' => '', 'shopinfo' => [], 'payinfo' => Cert::defaultPayinfo(), 'iscert' => 0, 'addtime' => now()]
        );

        $before = $cert->toArray();

        $cert->license = (string) $request->input('license', $cert->license);

        $payinfo = [];
        foreach ((array) $request->input('payinfo', []) as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $payinfo[] = [
                'sort' => (int) ($item['sort'] ?? (count($payinfo) + 1)),
                'type' => (string) ($item['type'] ?? 'url'),
                'name' => (string) ($item['name'] ?? ''),
                'account' => (string) ($item['account'] ?? ''),
                'image' => (string) ($item['image'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
                'cardno' => (string) ($item['cardno'] ?? ''),
                'carduser' => (string) ($item['carduser'] ?? ''),
                'cardbank' => (string) ($item['cardbank'] ?? ''),
            ];
        }

        // 按「排序」字段重排数组顺序：数组下标 0 即序号 1（默认优选平台）
        usort($payinfo, function ($a, $b) {
            if ($a['sort'] === $b['sort']) {
                return 0;
            }

            return $a['sort'] < $b['sort'] ? -1 : 1;
        });
        foreach ($payinfo as $k => $item) {
            $payinfo[$k]['sort'] = $k + 1;
        }

        $cert->payinfo = $payinfo ?: Cert::defaultPayinfo();
        $cert->iscert = (int) $request->input('iscert', $cert->iscert);
        $cert->save();

        $member->iscert = (int) $cert->iscert;
        $member->cert1 = $cert->license;
        $member->save();

        AdminLog::write('cert.update', '修改会员收款方式 #' . $id, 'cert', $cert->id, [
            'before' => $before,
            'after' => $cert->toArray(),
        ]);

        return redirect()->route('admin.certs.index')->with('status', '收款方式已保存');
    }

    /** 收货地址总览 */
    public function addresses(Request $request)
    {
        $query = Address::query();

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('userreal', 'like', "%{$kw}%")
                    ->orWhere('telephone', 'like', "%{$kw}%")
                    ->orWhere('address', 'like', "%{$kw}%")
                    ->orWhere('memberid', $kw);
            });
        }

        $rows = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $members = [];
        foreach ($rows as $r) {
            $members[$r->memberid] = Member::find($r->memberid);
        }

        return view('admin.addresses.index', compact('rows', 'members'));
    }

    public function deleteAddress(int $id)
    {
        $row = Address::findOrFail($id);
        AdminLog::write('address.destroy', '删除收货地址 #' . $id, 'address', $id, $row->toArray());
        $row->delete();

        return back()->with('status', '地址已删除');
    }
}
