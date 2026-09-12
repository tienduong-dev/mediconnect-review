@extends('admin/AdminLayout')

@section('page-title', 'Administrators')

@section('admin-content')
    <div class="page">

        @include('admin.partials.flash')

        <div class="helper-note">
            Everyone listed here has full control over the system. This screen is read only:
            an administrator account is created directly in the database, not from the panel.
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Administrators</h5>
                <a href="{{ url('Admin/MyAccount') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to my account
                </a>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Number</th>
                            <th class="text-center">Account</th>
                            <th>Last sign in</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $row)
                            <tr>
                                <td class="cell-title">
                                    {{ $row->name }}
                                    @if ($row->id === auth()->id())
                                        <span class="badge bg-primary">you</span>
                                    @endif
                                </td>
                                <td>{{ $row->email }}</td>
                                <td>{{ $row->number }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $row->account_status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $row->account_status }}
                                    </span>
                                </td>
                                <td>{{ optional($row->last_login_at)->format('Y-m-d H:i') ?: 'Never' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
