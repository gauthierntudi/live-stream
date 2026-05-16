{{--
  Pagination alignée sur les styles .pagination du layout admin (pas Bootstrap).
--}}
@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="Pagination">
        <div class="pagination">
            @if ($paginator->onFirstPage())
                <span class="pagination__disabled" aria-disabled="true">&laquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente">&laquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination__ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante">&raquo;</a>
            @else
                <span class="pagination__disabled" aria-disabled="true">&raquo;</span>
            @endif
        </div>
    </nav>
@endif
