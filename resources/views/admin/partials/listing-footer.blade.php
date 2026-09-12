{{--
    List footer: result count, page-size picker and pagination links.
    Usage: @include('admin.partials.listing-footer', ['paginator' => $cities])

    links() is given the bootstrap-5 view explicitly. The framework default
    is the Tailwind one, which prints its own "Showing 1 to 10 of 30 results"
    line and so duplicated the count on the left, next to unstyled links.
--}}
<div class="listing-foot">
    <div class="lf-count">
        @if ($paginator->total() > 0)
            Showing <strong>{{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong>
        @else
            No results
        @endif
    </div>

    <form method="GET" action="{{ url()->current() }}" class="lf-rows">
        @foreach (request()->except(['per_page', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        <label for="perPage">Rows</label>
        <select name="per_page" id="perPage" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ([10, 25, 50, 100] as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
            @endforeach
        </select>
    </form>

    @if ($paginator->hasPages())
        <div class="lf-pages">{{ $paginator->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

@once
    @push('styles')
        <style>
            .admin-page .listing-foot {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 14px;
            }

            .admin-page .lf-count {
                font-size: 12.5px;
                color: var(--muted);
            }

            .admin-page .lf-count strong { color: var(--ink-2); font-weight: 650; }

            .admin-page .lf-rows {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
            }

            .admin-page .lf-rows label {
                font-size: 12.5px;
                color: var(--muted);
                margin: 0;
            }

            /* Wide enough for "100" plus the dropdown arrow: the old
               width:auto clipped the number behind the caret. */
            .admin-page .lf-rows .form-select { width: 78px; }

            .admin-page .lf-pages { margin-left: auto; }

            @media (max-width: 575.98px) {
                .admin-page .lf-pages { margin-left: 0; width: 100%; }
            }
        </style>
    @endpush
@endonce
