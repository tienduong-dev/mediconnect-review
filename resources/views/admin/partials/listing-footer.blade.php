{{--
    List footer: result count, page-size picker and pagination links.
    Usage: @include('admin.partials.listing-footer', ['paginator' => $cities])
--}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="small text-muted">
        @if ($paginator->total() > 0)
            Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}
            of {{ $paginator->total() }}
        @else
            No results
        @endif
    </div>

    <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
        @foreach (request()->except(['per_page', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <label class="small text-muted mb-0">Rows</label>
        <select name="per_page" class="form-select form-select-sm" style="width:auto"
            onchange="this.form.submit()">
            @foreach ([10, 25, 50, 100] as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
            @endforeach
        </select>
    </form>

    <div>{{ $paginator->links() }}</div>
</div>
