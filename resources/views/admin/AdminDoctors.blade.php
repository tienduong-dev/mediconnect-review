@extends('admin/AdminLayout')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        @if (session('DoctorDeletedDone'))
            <div class="alert alert-success">{{ session('DoctorDeletedDone') }}</div>
        @endif
        @if (session('statusUpdate'))
            <div class="alert alert-success">{{ session('statusUpdate') }}</div>
        @endif

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Doctors</h5>
                <a href="{{ url('Admin/DoctorRegister') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-person-plus"></i> New Doctor
                </a>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width:1%">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Number</th>
                            <th class="text-center">Account</th>
                            <th class="text-center">Profile status</th>
                            <th class="text-center text-nowrap" style="width:1%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($doctors as $index => $d)
                            <tr>
                                <th>{{ $doctors->firstItem() + $index }}</th>
                                <td>{{ $d->name }}</td>
                                <td>{{ $d->email }}</td>
                                <td>{{ $d->number }}</td>

                                <td class="text-center">
                                    <span class="badge {{ $d->account_status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $d->account_status }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    @if ($d->doctorDetails)
                                        <form action="{{ url('Admin/Doctor/updateStatus') }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $d->doctorDetails->id }}">
                                            <input type="hidden" name="name" value="{{ $d->name }}">
                                            <button type="submit"
                                                class="btn btn-sm status-pill {{ $d->doctorDetails->status === 'Active' ? 'btn-success' : 'btn-secondary' }}"
                                                title="Click to toggle status">
                                                {{ $d->doctorDetails->status }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-warning text-dark">No profile</span>
                                    @endif
                                </td>

                                <td class="text-center text-nowrap">
                                    <a href="{{ url('Admin/DoctorProfile', $d->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-person-lines-fill"></i> Profile
                                    </a>

                                    <form method="POST"
                                        action="{{ url('Admin/User/ToggleAccountStatus', $d->id) }}" class="d-inline"
                                        onsubmit="return confirm('{{ $d->account_status === 'Active' ? 'Deactivate' : 'Reactivate' }} the account of {{ $d->name }}?')">
                                        @csrf
                                        <button
                                            class="btn btn-sm {{ $d->account_status === 'Active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            <i class="bi {{ $d->account_status === 'Active' ? 'bi-lock' : 'bi-unlock' }}"></i>
                                            {{ $d->account_status === 'Active' ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No doctors yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $doctors->links() }}
            </div>
        </div>
    </section>
@endsection
