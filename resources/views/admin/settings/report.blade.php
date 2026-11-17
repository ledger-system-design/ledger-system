@extends('admin.layout')

@section('title', '运营报表')

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">各会员采购与收益汇总</div>
            <a class="btn btn--sm" href="{{ route('admin.balances.index') }}">查看资金明细</a>
        </div>

        <div class="card__body card__body--flush">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>ID</th><th>会员</th><th>账号</th><th>直推人</th>
                            <th class="text-right">个人采购额</th><th class="text-right">已到账金额</th><th class="text-right">任务数</th>
                            <th class="text-right">订单收益</th><th class="text-right">直推收益</th>
                            <th class="text-right">余额</th><th class="text-right">已提现</th>
                            <th class="text-right">团队人数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $m)
                            @php
                                $d = $data[$m->id] ?? [];
                                $parent = $m->pid ? \App\Models\Member::find($m->pid) : null;
                            @endphp
                            <tr>
                                <td class="mono">{{ $m->id }}</td>
                                <td><a href="{{ route('admin.members.show', $m->id) }}">{{ $m->userreal ?: $m->nickname }}</a></td>
                                <td class="mono">{{ $m->username }}</td>
                                <td class="small">{{ $parent->userreal ?? '-' }}</td>
                                <td class="text-right num">¥{{ number_format($d['flow'] ?? 0, 2) }}</td>
                                <td class="text-right num">¥{{ number_format($d['arrived'] ?? 0, 2) }}</td>
                                <td class="text-right num">{{ $d['orders'] ?? 0 }}</td>
                                <td class="text-right num">¥{{ number_format($m->bonus1, 2) }}</td>
                                <td class="text-right num">¥{{ number_format($m->bonus2, 2) }}</td>
                                <td class="text-right num">¥{{ number_format($m->balance1, 2) }}</td>
                                <td class="text-right num">¥{{ number_format($m->withdrawed, 2) }}</td>
                                <td class="text-right num">{{ \App\Models\Member::where('pid', $m->id)->count() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="empty">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">{{ $rows->links('admin.pagination') }}</div>
    </div>
@endsection
