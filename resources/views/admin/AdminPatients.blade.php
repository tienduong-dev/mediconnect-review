@extends('admin/AdminLayout')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Patients</h5>
                <span class="text-white small">Total on this page: {{ $patients->count() }}</span>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width:1%">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Number</th>
                            <th>Address</th>
                            <th class="text-center">Account status</th>
                            <th class="text-center text-nowrap" style="width:1%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $index => $p)
                            <tr>
                                <th>{{ $patients->firstItem() + $index }}</th>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->email }}</td>
                                <td>{{ $p->number }}</td>
                                <td>{{ Str::limit($p->address, 40) ?: '—' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $p->account_status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $p->account_status }}
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <form method="POST"
                                        action="{{ url('Admin/User/ToggleAccountStatus', $p->id) }}" class="d-inline"
                                        onsubmit="return confirm('{{ $p->account_status === 'Active' ? 'Deactivate' : 'Reactivate' }} the account of {{ $p->name }}?')">
                                        @csrf
                                        <button
                                            class="btn btn-sm {{ $p->account_status === 'Active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            <i class="bi {{ $p->account_status === 'Active' ? 'bi-lock' : 'bi-unlock' }}"></i>
                                            {{ $p->account_status === 'Active' ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No patients yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $patients->links() }}
            </div>
        </div>
    </section>
@endsection
