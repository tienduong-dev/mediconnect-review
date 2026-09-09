@extends('admin/AdminLayout')

@section('page-title', 'Medical Contents')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Medical Contents</h5>
                <a href="{{ url('Admin/MedicalContents/Create') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-lg"></i> New Article
                </a>
            </div>

            {{-- Status tabs. Each one keeps the other filters in the query string. --}}
            <div class="card-body border-bottom pb-0">
                <ul class="nav nav-tabs card-header-tabs">
                    @foreach ($counts as $key => $total)
                        @php
                            $value  = $key === 'All' ? null : $key;
                            $params = array_merge(request()->except(['status', 'page']), $value ? ['status' => $value] : []);
                            $active = request('status') === $value || (!request('status') && $key === 'All');
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ $active ? 'active' : '' }}"
                                href="{{ url('Admin/MedicalContents') }}?{{ http_build_query($params) }}">
                                {{ $key }}
                                <span class="badge bg-secondary">{{ $total }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ url('Admin/MedicalContents') }}" class="row g-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">

                    <div class="col-md-5">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control"
                            placeholder="Search by title or summary...">
                    </div>
                    <div class="col-md-4">
                        <select name="content_type" class="form-select">
                            <option value="">-- All types --</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(request('content_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ url('Admin/MedicalContents') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th>@include('admin.partials.sort', ['label' => 'Title', 'field' => 'title'])</th>
                            <th>@include('admin.partials.sort', ['label' => 'Type', 'field' => 'content_type'])</th>
                            <th>Author</th>
                            <th class="text-center">@include('admin.partials.sort', ['label' => 'Status', 'field' => 'status'])</th>
                            <th>@include('admin.partials.sort', ['label' => 'Published', 'field' => 'published_at'])</th>
                            <th class="text-center text-nowrap" style="width:1%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contents as $c)
                            <tr>
                                <td>
                                    {{ $c->title }}
                                    <div class="small text-muted">/{{ $c->slug }}</div>
                                </td>
                                <td class="text-nowrap">{{ $types[$c->content_type] ?? $c->content_type }}</td>
                                <td>{{ $c->author->name ?? '—' }}</td>
                                <td class="text-center">
                                    @include('admin.medical-contents.status-badge', ['content' => $c])
                                </td>
                                <td class="text-nowrap">
                                    {{ optional($c->published_at)->format('Y-m-d H:i') ?: '—' }}
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ url('Admin/MedicalContents/Show', $c->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Review
                                    </a>
                                    <a href="{{ url('Admin/MedicalContents/Edit', $c->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No articles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                @include('admin.partials.listing-footer', ['paginator' => $contents])
            </div>
        </div>
    </section>
@endsection
