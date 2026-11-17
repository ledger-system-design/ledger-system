@if ($paginator->hasPages())
    @if ($paginator->onFirstPage())
        <span>上一页</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}">上一页</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span>{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="is-active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}">下一页</a>
    @else
        <span>下一页</span>
    @endif

    <span class="muted">共 {{ $paginator->total() }} 条 / {{ $paginator->lastPage() }} 页</span>
@endif
