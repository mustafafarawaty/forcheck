@if ($paginator->hasPages())
    <nav class="student-pagination" aria-label="Pagination Navigation">
        <ul class="student-pagination-list">
            @if ($paginator->onFirstPage())
                <li class="student-pagination-item is-disabled" aria-disabled="true">
                    <span class="student-pagination-link">‹</span>
                </li>
            @else
                <li class="student-pagination-item">
                    <a class="student-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="السابق">‹</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="student-pagination-item is-gap" aria-disabled="true">
                        <span class="student-pagination-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="student-pagination-item is-active" aria-current="page">
                                <span class="student-pagination-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="student-pagination-item">
                                <a class="student-pagination-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="student-pagination-item">
                    <a class="student-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="التالي">›</a>
                </li>
            @else
                <li class="student-pagination-item is-disabled" aria-disabled="true">
                    <span class="student-pagination-link">›</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
