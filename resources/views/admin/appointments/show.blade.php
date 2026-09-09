@extends('admin/AdminLayout')

@section('page-title', 'Appointment ' . $appointment->appointment_number)

@section('admin-content')
    @php($fs = $appointment->facilitySpecialization)
    @php($canAct = in_array($appointment->status, ['Pending', 'Confirmed'], true))

    <section class="container">

        @include('admin.partials.flash')

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">{{ $appointment->appointment_number }}</h5>
                        @include('admin.appointments.status-badge', ['status' => $appointment->status])
                    </div>

                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered m-0">
                            <tbody>
                                <tr>
                                    <th style="width:210px;">Patient</th>
                                    <td>
                                        {{ $appointment->patient_name }}
                                        @if ($appointment->patient)
                                            &nbsp;
                                            <a href="{{ url('Admin/Patients/Show', $appointment->patient->id) }}"
                                                class="small">(account)</a>
                                        @else
                                            <span class="small text-muted">(booked for a relative)</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>Phone</th><td>{{ $appointment->patient_phone }}</td></tr>
                                <tr><th>Email</th><td>{{ $appointment->patient_email ?: '—' }}</td></tr>
                                <tr>
                                    <th>Doctor</th>
                                    <td>{{ $appointment->doctorAssignment->doctor->user->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Facility</th>
                                    <td>
                                        {{ $fs->facility->name ?? '—' }}
                                        <div class="small text-muted">
                                            {{ $fs->facility->address ?? '' }}
                                            @if ($appointment->doctorAssignment?->room_number)
                                                &middot; Room {{ $appointment->doctorAssignment->room_number }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr><th>Specialization</th><td>{{ $fs->specialization->name ?? '—' }}</td></tr>
                                <tr>
                                    <th>Date &amp; time</th>
                                    <td>
                                        {{ $appointment->appointment_date?->format('Y-m-d') }}
                                        &nbsp;
                                        {{ substr((string) $appointment->start_time, 0, 5) }}&ndash;{{ substr((string) $appointment->end_time, 0, 5) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Consultation fee</th>
                                    <td>{{ number_format($appointment->doctorAssignment->consultation_fee ?? 0, 2) }}</td>
                                </tr>
                                <tr><th>Reason</th><td>{{ $appointment->examination_reason ?: '—' }}</td></tr>
                                <tr><th>Symptoms</th><td>{{ $appointment->symptoms ?: '—' }}</td></tr>
                                @if ($appointment->rejection_reason)
                                    <tr class="table-danger">
                                        <th>Rejection reason</th>
                                        <td>{{ $appointment->rejection_reason }}</td>
                                    </tr>
                                @endif
                                @if ($appointment->cancellation_reason)
                                    <tr>
                                        <th>Cancellation reason</th>
                                        <td>{{ $appointment->cancellation_reason }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Full audit trail from appointment_histories --}}
                <div class="card shadow mt-4">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-white">History</h5>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered m-0">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Action</th>
                                    <th>Status change</th>
                                    <th>By</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($appointment->histories->sortByDesc('created_at') as $h)
                                    <tr>
                                        <td class="text-nowrap">{{ $h->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $h->action }}</td>
                                        <td class="text-nowrap">
                                            {{ $h->old_status ?? '—' }} &rarr; {{ $h->new_status }}
                                        </td>
                                        <td>{{ $h->changedBy->name ?? 'System' }}</td>
                                        <td>{{ $h->note ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No history recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-white">Actions</h5>
                    </div>
                    <div class="card-body d-grid gap-2">

                        @if ($appointment->status === 'Pending')
                            <form method="POST" action="{{ url('Admin/Appointments/Confirm', $appointment->id) }}"
                                onsubmit="return confirm('Confirm this appointment? The patient and the doctor will be notified.')">
                                @csrf
                                <button class="btn btn-primary w-100">
                                    <i class="bi bi-check2-circle"></i> Confirm appointment
                                </button>
                            </form>
                        @endif

                        @if ($canAct)
                            <a href="{{ url('Admin/Appointments/Adjust', $appointment->id) }}"
                                class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left-right"></i> Adjust doctor or time
                            </a>
                        @endif

                        <a href="{{ url('Admin/Appointments') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to list
                        </a>

                        @unless ($canAct)
                            <div class="small text-muted mt-2">
                                This appointment is <strong>{{ $appointment->status }}</strong>, so no admin action is
                                available. Completed and NoShow are recorded by the doctor.
                            </div>
                        @endunless
                    </div>
                </div>

                @if ($appointment->status === 'Pending')
                    <div class="card shadow mt-4 border-danger-subtle">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-danger fw-bold">Reject request</h6>
                        </div>
                        <form method="POST" action="{{ url('Admin/Appointments/Reject', $appointment->id) }}"
                            onsubmit="return confirm('Reject this request? The slot is released and the patient is notified.')">
                            @csrf
                            <div class="card-body">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <textarea name="rejection_reason" class="form-control" rows="3" maxlength="500"
                                    placeholder="Shown to the patient" required>{{ old('rejection_reason') }}</textarea>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-outline-danger w-100">
                                    <i class="bi bi-x-circle"></i> Reject appointment
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
