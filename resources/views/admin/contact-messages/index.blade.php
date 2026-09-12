@extends('admin/AdminLayout')

@section('page-title', 'Contact Messages')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Contact Messages</h5>
                <span class="text-white small">Sent from the public contact form.</span>
            </div>

            {{-- Inbox tabs. Each keeps the other filters in the query string. --}}
            <div class="card-body border-bottom pb-0">
                <ul class="nav nav-tabs card-header-tabs">
                    @foreach ($counts as $key => $total)
                        @php
                            $value  = $key === 'All' ? null : $key;
                            $params = array_merge(request()->except(['status', 'page']), $value ? ['status' => $value] : []);
                            $active = request('status') === $value || (!request('status') && $key === 'All');
                            $label  = $key === 'InProgress' ? 'In progress' : $key;
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ $active ? 'active' : '' }}"
                                href="{{ url('Admin/ContactMessages') }}?{{ http_build_query($params) }}">
                                {{ $label }}
                                <span class="badge {{ $key === 'New' && $total > 0 ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $total }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ url('Admin/ContactMessages') }}" class="row g-2">
                    <input type="hidden" name="status" value="{{ request('status') }}">

                    <div class="col-md-5">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control"
                            placeholder="Search by name, email, phone or content...">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"
                            title="From date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"
                            title="To date">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ url('Admin/ContactMessages') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th>@include('admin.partials.sort', ['label' => 'From', 'field' => 'name'])</th>
                            <th>Contact</th>
                            <th>Subject</th>
                            <th class="text-center">@include('admin.partials.sort', ['label' => 'Status', 'field' => 'status'])</th>
                            <th>@include('admin.partials.sort', ['label' => 'Received', 'field' => 'created_at'])</th>
                            <th class="text-center text-nowrap" style="width:1%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($messages as $m)
                            <tr @class(['fw-semibold' => $m->status === 'New'])>
                                <td>
                                    {{ $m->name }}
                                    @if ($m->user)
                                        <div class="small text-muted">
                                            <i class="bi bi-person-check"></i> registered account
                                        </div>
                                    @else
                                        <div class="small text-muted">guest</div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $m->email }}
                                    <div class="text-muted">{{ $m->phone }}</div>
                                </td>
                                <td>
                                    {{ $m->subject ?: '—' }}
                                    <div class="small text-muted">
                                        {{ \Illuminate\Support\Str::limit($m->message, 70) }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @include('admin.contact-messages.status-badge', ['status' => $m->status])
                                </td>
                                <td class="text-nowrap small">
                                    {{ optional($m->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ url('Admin/ContactMessages/Show', $m->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-envelope-open"></i> Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No messages found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                @include('admin.partials.listing-footer', ['paginator' => $messages])
            </div>
        </div>
    </section>
@endsection
