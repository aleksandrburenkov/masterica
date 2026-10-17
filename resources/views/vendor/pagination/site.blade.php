@if ($paginator->hasPages())
    <nav aria-label="Постраничная навигация">
        <div class="filters" style="margin:0">
            @if ($paginator->onFirstPage())
                <span class="filter-pill" style="opacity:.5">Назад</span>
            @else
                <a class="filter-pill" href="{{ $paginator->previousPageUrl() }}" rel="prev">Назад</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="filter-pill" style="border-style:dashed">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="filter-pill is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="filter-pill" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="filter-pill" href="{{ $paginator->nextPageUrl() }}" rel="next">Вперёд</a>
            @else
                <span class="filter-pill" style="opacity:.5">Вперёд</span>
            @endif
        </div>
    </nav>
@endif
