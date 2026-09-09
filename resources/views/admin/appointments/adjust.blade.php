@extends('admin/AdminLayout')

@section('page-title', 'Adjust ' . $appointment->appointment_number)

@section('admin-content')
    @php($fs = $appointment->facilitySpecialization)

    <section class="container">

        @include('admin.partials.flash')

        <div class="helper-note">
            Choosing a slot also moves the appointment to the doctor who owns that slot. Only open slots from
            <strong>{{ $fs->facility->name ?? '?' }} &mdash; {{ $fs->specialization->name ?? '?' }}</strong> are listed,
            so the patient keeps the facility and specialization they booked.
        </div>

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">Adjust {{ $appointment->appointment_number }}</h5>
            </div>

            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Patient</div>
                        <div class="fw-semibold">{{ $appointment->patient_name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Current doctor</div>
                        <div class="fw-semibold">{{ $appointment->doctorAssignment->doctor->user->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Current time</div>
                        <div class="fw-semibold">
                            {{ $appointment->appointment_date?->format('Y-m-d') }}
                            {{ substr((string) $appointment->start_time, 0, 5) }}
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ url('Admin/Appointments/Adjust', $appointment->id) }}">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">New slot <span class="text-danger">*</span></label>
                        <select name="slot_id" class="form-select" required>
                            <option value="">-- Choose a slot --</option>
                            @forelse ($slots as $slot)
                                <option value="{{ $slot->id }}"
                                    @selected(old('slot_id', $appointment->slot_id) == $slot->id)>
                                    {{ $slot->slot_date?->format('Y-m-d') }}
                                    {{ substr((string) $slot->start_time, 0, 5) }}&ndash;{{ substr((string) $slot->end_time, 0, 5) }}
                                    &nbsp;|&nbsp;
                                    {{ $slot->doctorAssignment->doctor->user->name ?? 'Unknown doctor' }}
                                    @if ((int) $slot->id === (int) $appointment->slot_id)
                                        (current)
                                    @endif
                                </option>
                            @empty
                                <option value="" disabled>No open slot available for this specialization</option>
                            @endforelse
                        </select>
                        @if ($slots->isEmpty())
                            <small class="text-danger">
                                Nothing to move to. Ask the doctor to open more slots, or reject the request instead.
                            </small>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="3" maxlength="500"
                            placeholder="Why is this being moved? Included in the notification.">{{ old('note') }}</textarea>
                    </div>

                    <div class="alert alert-warning mb-0">
                        The old slot is released and the new one is taken in a single transaction, so the slot cannot be
                        double booked. The patient, the new doctor and the previous doctor are all notified.
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary" @disabled($slots->isEmpty())>
                        <i class="bi bi-arrow-left-right"></i> Apply adjustment
                    </button>
                    <a href="{{ url('Admin/Appointments/Show', $appointment->id) }}"
                        class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>
@endsection
