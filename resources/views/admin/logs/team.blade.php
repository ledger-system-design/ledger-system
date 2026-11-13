@extends('admin.layout')

@section('title', '推荐关系')

@php
    if (! function_exists('renderTree')) {
        function renderTree($nodes)
        {
            if (! $nodes) {
                return;
            }
            echo '<ul class="tree">';
            foreach ($nodes as $node) {
                $m = $node['member'];
                echo '<li>';
                echo '<a class="node" href="' . route('admin.members.show', $m->id) . '">';
                echo '<strong>' . e($m->userreal ?: $m->nickname) . '</strong>';
                echo '<span class="muted mono">' . e($m->username) . '</span>';
                echo '<span class="badge badge--gray">' . e($m->gradename) . '</span>';
                if ($m->is_shop) {
                    echo '<span class="badge badge--red">店长</span>';
                }
                echo '<span class="muted small">余额 ¥' . number_format($m->balance1, 2) . '</span>';
                echo '<span class="muted small">直推 ' . \App\Models\Member::where('pid', $m->id)->count() . ' 人</span>';
                echo '</a>';
                renderTree($node['children']);
                echo '</li>';
            }
            echo '</ul>';
        }
    }
@endphp

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">推荐关系树（第一代 → 第二代 → 第三代）</div>
            <div class="btn-row">
                <a class="btn btn--sm" href="{{ route('admin.team') }}">重置</a>
            </div>
        </div>

        <div class="card__body">
            <form class="filter-bar" method="get">
                <div class="field" style="min-width:260px;">
                    <label>选择根节点</label>
                    <select name="root" onchange="this.form.submit()">
                        @foreach ($roots as $r)
                            <option value="{{ $r->id }}" @selected($root && $root->id === $r->id)>
                                #{{ $r->id }} · {{ $r->userreal ?: $r->nickname }}（{{ $r->username }}）
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn btn--primary" type="submit">查看</button>
                </div>
            </form>
        </div>

        <div class="card__body">
            @if ($root)
                <div class="node" style="margin-bottom:12px;display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:#fdecea;border-radius:8px;">
                    <strong>{{ $root->userreal ?: $root->nickname }}</strong>
                    <span class="mono muted">{{ $root->username }}</span>
                    <span class="badge">#{{ $root->id }}</span>
                </div>
                @if ($tree)
                    @php renderTree($tree); @endphp
                @else
                    <div class="empty">该会员暂无下级成员</div>
                @endif
            @else
                <div class="empty">暂无会员数据</div>
            @endif
        </div>

        <div class="card__body muted small" style="border-top:1px solid var(--border);">
            收益规则：第二代（直推）的流水会给第一代带来 0.5% 直推收益；
            第三代（间推）的流水只给第二代带来收益，不给第一代产生任务收益。
        </div>
    </div>
@endsection
