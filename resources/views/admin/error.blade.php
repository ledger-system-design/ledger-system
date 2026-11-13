@extends('admin.layout')

@section('title', '操作没有完成')

@section('content')
    <div class="card">
        <div class="card__head">
            <div class="card__title">操作没有完成</div>
        </div>
        <div class="card__body">
            <div class="alert alert--err" style="font-size:15px;line-height:1.8;">
                {{ $message ?? '操作失败，请检查填写内容后重试' }}
            </div>

            @if (!empty($hint))
                <p style="color:#6b7280;font-size:13px;margin:10px 0 0;">{{ $hint }}</p>
            @endif

            <div class="btn-row" style="margin-top:18px;">
                @if (!empty($reload))
                    <a class="btn btn--primary" href="{{ $backUrl ?? route('admin.dashboard') }}">返回上一页刷新后重试</a>
                @else
                    <a class="btn btn--primary" href="{{ $backUrl ?? route('admin.dashboard') }}">返回上一页修改</a>
                @endif
                <a class="btn" href="{{ route('admin.dashboard') }}">回到数据概览</a>
            </div>
        </div>
    </div>
@endsection
