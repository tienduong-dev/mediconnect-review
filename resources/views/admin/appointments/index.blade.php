@extends('admin/AdminLayout')

@section('page-title', 'Appointments')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Appointments</h5>
                <span class="text-white small">Admin confirms, adjusts or rejects. Doctors close them after the visit.</span>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ url('Admin/Appointments') }}" class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control"
                            placeholder="Number, patient or phone...">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">-- All statuses --</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="facility_id" class="form-select">
                            <option value="">-- All facilities --</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(request('facility_id') == $f->id)>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"
                            title="From date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"
                            title="To date">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ url('Admin/Appointments') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th>@include('admin.partials.sort', ['label' => 'Number', 'field' => 'appointment_number'])</th>
                            <th>@include('admin.partials.sort', ['label' => 'Patient', 'field' => 'patient_name'])</th>
                            <th>Doctor</th>
                            <th>Facility</th>
                            <th>@include('admin.partials.sort', ['label' => 'Date', 'field' => 'appointment_date'])</th>
                            <th>Time</th>
                            <th class="text-center">@include('admin.partials.sort', ['label' => 'Status', 'field' => 'status'])</th>
                            <th class="text-center text-nowrap" style="width:1%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $a)
                            @php($fs = $a->facilitySpecialization)
                            <tr>
                                <td class="text-nowrap">{{ $a->appointment_number }}</td>
                                <td>
                                    {{ $a->patient_name }}
                                    <div class="small text-muted">{{ $a->patient_phone }}</div>
                                </td>
                                <td>
                                    {{ $a->doctorAssignment->doctor->user->name ?? '—' }}
                                    <div class="small text-muted">{{ $fs->specialization->name ?? '' }}</div>
                                </td>
                                <td>{{ $fs->facility->name ?? '—' }}</td>
                                <td class="text-nowrap">{{ $a->appointment_date?->format('Y-m-d') }}</td>
                                <td class="text-nowrap">
                                    {{ substr((string) $a->start_time, 0, 5) }}&ndash;{{ substr((string) $a->end_time, 0, 5) }}
                                </td>
                                <td class="text-center">
                                    @include('admin.appointments.status-badge', ['status' => $a->status])
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ url('Admin/Appointments/Show', $a->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No appointments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                @include('admin.partials.listing-footer', ['paginator' => $appointments])
            </div>
        </div>
    </section>
@endsection
