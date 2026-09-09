{{--
    Sortable column header.
    Usage: @include('admin.partials.sort', ['label' => 'Name', 'field' => 'name'])
    Keeps every other filter in the query string and always resets to page 1.
--}}
@php
    $currentSort = request('sort');
    $currentDir  = request('dir', 'asc');
    $nextDir     = ($currentSort === $field && $currentDir === 'asc') ? 'desc' : 'asc';
    $params      = array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => $field, 'dir' => $nextDir]);
@endphp

<a href="{{ url()->current() }}?{{ http_build_query($params) }}" class="sort-link">
    {{ $label }}
    @if ($currentSort === $field)
        <i class="bi bi-caret-{{ $currentDir === 'desc' ? 'down' : 'up' }}-fill"></i>
    @else
        <i class="bi bi-arrow-down-up"></i>
    @endif
</a>
