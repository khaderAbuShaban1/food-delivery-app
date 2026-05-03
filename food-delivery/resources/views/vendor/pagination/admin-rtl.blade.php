@if ($paginator->hasPages())
    <nav class="admin-pagination-nav" aria-label="تصفح الصفحات">
        <div class="admin-pagination-inner">
            <p class="admin-pagination-info">
                @if ($paginator->firstItem())
                    عرض <strong>{{ $paginator->firstItem() }}</strong> – <strong>{{ $paginator->lastItem() }}</strong> من <strong>{{ $paginator->total() }}</strong>
                @else
                    <strong>{{ $paginator->count() }}</strong> نتيجة
                @endif
            </p>
            <div class="admin-pagination-links">
                @if ($paginator->onFirstPage())
                    <span class="admin-page-pill disabled">السابق</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="admin-page-pill" rel="prev">السابق</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="admin-page-pill dots">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="admin-page-pill active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="admin-page-pill">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="admin-page-pill" rel="next">التالي</a>
                @else
                    <span class="admin-page-pill disabled">التالي</span>
                @endif
            </div>
        </div>
    </nav>
@endif
