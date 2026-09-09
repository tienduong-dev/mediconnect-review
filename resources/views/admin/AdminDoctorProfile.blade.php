@extends('admin/AdminLayout')

@section('page-title', 'Doctor Details')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        @if (session('ThisDoctorEditedOkay'))
            <div class="alert alert-success">{{ session('ThisDoctorEditedOkay') }}</div>
        @endif
        @if (session('doctorDetailsAddOkay'))
            <div class="alert alert-success">{{ session('doctorDetailsAddOkay') }}</div>
        @endif

        <div class="row g-4">

            {{-- Left: profile details --}}
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">{{ $user->name }}</h5>
                        <span class="badge {{ $doctor->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ $doctor->status }}
                        </span>
                    </div>

                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered m-0">
                            <tbody>
                                <tr>
                                    <th style="width:200px;">Email</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>Mobile number</th>
                                    <td>{{ $user->number }}</td>
                                </tr>
                                <tr>
                                    <th>Account status</th>
                                    <td>{{ $user->account_status }}</td>
                                </tr>
                                <tr>
                                    <th>Expertise</th>
                                    <td>{{ $doctor->expertise }}</td>
                                </tr>
                                <tr>
                                    <th>Education</th>
                                    <td>{{ $doctor->education }}</td>
                                </tr>
                                <tr>
                                    <th>Experience</th>
                                    <td>{{ $doctor->experience }} year(s)</td>
                                </tr>
                                <tr>
                                    <th>Profession</th>
                                    <td>{{ $doctor->profession }}</td>
                                </tr>
                                <tr>
                                    <th>Qualifications</th>
                                    <td>{{ $doctor->qualifications ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>License number</th>
                                    <td>{{ $doctor->license_number ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Bio</th>
                                    <td>{{ $doctor->bio ?: '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Assignments replace the old "available days / time" block:
                     where the doctor works, in which room, and at what fee. --}}
                <div class="card shadow mt-4">
                    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">Assignments</h5>
                        <a href="{{ url('Admin/DoctorAssignments/Create') }}" class="btn btn-sm btn-light">
                            <i class="bi bi-plus-lg"></i> New Assignment
                        </a>
                    </div>

                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered m-0">
                            <thead>
                                <tr>
                                    <th>Facility</th>
                                    <th>City</th>
                                    <th>Specialization</th>
                                    <th>Room</th>
                                    <th class="text-end">Fee</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($doctor->assignments as $assignment)
                                    @php($fs = $assignment->facilitySpecialization)
                                    <tr>
                                        <td>{{ $fs->facility->name ?? '—' }}</td>
                                        <td>{{ $fs->facility->city->name ?? '—' }}</td>
                                        <td>{{ $fs->specialization->name ?? '—' }}</td>
                                        <td>{{ $assignment->room_number ?? '—' }}</td>
                                        <td class="text-end">{{ number_format($assignment->consultation_fee, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $assignment->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $assignment->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Not assigned to any facility yet, so this doctor will not appear in patient
                                            search results.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right: photo and actions --}}
            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        {{-- The seed data stores 'person1.jpg', which does not exist in
                             public/upload/doctors, so fall back to initials instead of a
                             broken image icon. --}}
                        @php($photoPath = $doctor->image ? public_path('upload/doctors/' . $doctor->image) : null)
                        @if ($photoPath && is_file($photoPath))
                            <img src="{{ asset('upload/doctors/' . $doctor->image) }}" alt="{{ $user->name }}"
                                class="img-fluid rounded mb-3" style="border:1px solid #e4e9f0;">
                        @else
                            <div class="avatar-fallback mb-3">
                                {{ \Illuminate\Support\Str::of($user->name)->substr(0, 1)->upper() }}
                            </div>
                            <div class="small text-muted mb-3">No photo uploaded</div>
                        @endif

                        <div class="d-grid gap-2">
                            <a href="{{ url('Admin/Doctor/EditThisProfile', $user->id) }}" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Edit profile
                            </a>
                            <a href="{{ url('Admin/Doctors') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to doctors
                            </a>

                            {{-- Controls whether the doctor is visible to patients --}}
                            <form action="{{ url('Admin/Doctor/updateStatus') }}" method="POST"
                                onsubmit="return confirm('{{ $doctor->status === 'Active' ? 'Hide this doctor from patient search?' : 'Make this doctor visible to patients again?' }}')">
                                @csrf
                                <input type="hidden" name="id" value="{{ $doctor->id }}">
                                <input type="hidden" name="name" value="{{ $user->name }}">
                                <button class="btn w-100 {{ $doctor->status === 'Active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                    <i class="bi {{ $doctor->status === 'Active' ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                    {{ $doctor->status === 'Active' ? 'Hide from patient search' : 'Show in patient search' }}
                                </button>
                            </form>
                            {{-- Accounts are deactivated, never deleted: a hard delete would
                                 cascade away assignments, schedules and slots, and detach the
                                 doctor from past appointments. --}}
                            <form method="POST" action="{{ url('Admin/User/ToggleAccountStatus', $user->id) }}"
                                onsubmit="return confirm('{{ $user->account_status === 'Active' ? 'Deactivate' : 'Reactivate' }} the account of {{ $user->name }}?')">
                                @csrf
                                <button
                                    class="btn w-100 {{ $user->account_status === 'Active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                    <i class="bi {{ $user->account_status === 'Active' ? 'bi-lock' : 'bi-unlock' }}"></i>
                                    {{ $user->account_status === 'Active' ? 'Deactivate account' : 'Reactivate account' }}
                                </button>
                            </form>
                            <small class="text-muted">
                                A deactivated account cannot sign in, but all of its records are kept.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
