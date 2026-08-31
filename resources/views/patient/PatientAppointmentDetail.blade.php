@extends('patient.PatientLayout')

@section('patient-content')

@php

    $assignment =
        $appointment->doctorAssignment;

    $doctor =
        $assignment?->doctor;

    $doctorUser =
        $doctor?->user;

    $facilitySpecialization =
        $assignment?->facilitySpecialization;

    $facility =
        $facilitySpecialization?->facility;

    $specialization =
        $facilitySpecialization?->specialization;

@endphp


<div class="container my-5">

    <div class="mb-4">

        <a
            href="{{ route('patient.appointments') }}"
            class="text-decoration-none"
        >
            ← Back to My Appointments
        </a>

    </div>


    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="bg-white rounded-4 shadow-sm p-4">


                {{-- Header --}}
                <div class="d-flex justify-content-between
                            align-items-start flex-wrap
                            gap-3 mb-4">

                    <div>

                        <h2 class="black mb-1">
                            Appointment Details
                        </h2>

                        <p class="pra mb-0">
                            {{ $appointment->appointment_number }}
                        </p>

                    </div>


                    <div>

                        @switch($appointment->status)

                            @case('Pending')
                                <span class="badge bg-warning text-dark fs-6">
                                    Pending
                                </span>
                                @break

                            @case('Confirmed')
                                <span class="badge bg-success fs-6">
                                    Confirmed
                                </span>
                                @break

                            @case('Rejected')
                                <span class="badge bg-danger fs-6">
                                    Rejected
                                </span>
                                @break

                            @case('Cancelled')
                                <span class="badge bg-secondary fs-6">
                                    Cancelled
                                </span>
                                @break

                            @case('Completed')
                                <span class="badge bg-primary fs-6">
                                    Completed
                                </span>
                                @break

                            @case('NoShow')
                                <span class="badge bg-dark fs-6">
                                    No Show
                                </span>
                                @break

                        @endswitch

                    </div>

                </div>


                {{-- Doctor --}}
                <div class="border rounded-4 p-4 mb-4">

                    <h5 class="black mb-3">
                        Doctor Information
                    </h5>

                    <p class="mb-2">
                        <strong>Doctor:</strong>
                        {{ $doctorUser?->name ?? 'N/A' }}
                    </p>

                    <p class="mb-2">
                        <strong>Specialization:</strong>
                        {{ $specialization?->name ?? 'N/A' }}
                    </p>

                    <p class="mb-2">
                        <strong>Facility:</strong>
                        {{ $facility?->name ?? 'N/A' }}
                    </p>

                    <p class="mb-2">
                        <strong>Address:</strong>
                        {{ $facility?->address ?? 'N/A' }}
                    </p>

                    <p class="mb-0">
                        <strong>Room:</strong>
                        {{ $assignment?->room_number ?? 'N/A' }}
                    </p>

                </div>


                {{-- Appointment --}}
                <div class="border rounded-4 p-4 mb-4">

                    <h5 class="black mb-3">
                        Appointment Information
                    </h5>

                    <p class="mb-2">

                        <strong>Date:</strong>

                        {{ $appointment->appointment_date
                            ->format('d/m/Y') }}

                    </p>

                    <p class="mb-2">

                        <strong>Time:</strong>

                        {{ \Carbon\Carbon::parse(
                            $appointment->start_time
                        )->format('H:i') }}

                        -

                        {{ \Carbon\Carbon::parse(
                            $appointment->end_time
                        )->format('H:i') }}

                    </p>

                    <p class="mb-2">

                        <strong>Booked At:</strong>

                        {{ $appointment->booked_at
                            ? $appointment->booked_at
                                ->format('d/m/Y H:i')
                            : 'N/A' }}

                    </p>

                    <p class="mb-0">

                        <strong>Status:</strong>

                        {{ $appointment->status }}

                    </p>

                </div>


                {{-- Patient --}}
                <div class="border rounded-4 p-4 mb-4">

                    <h5 class="black mb-3">
                        Patient Information
                    </h5>

                    <p class="mb-2">
                        <strong>Name:</strong>
                        {{ $appointment->patient_name }}
                    </p>

                    <p class="mb-2">
                        <strong>Email:</strong>
                        {{ $appointment->patient_email ?? 'N/A' }}
                    </p>

                    <p class="mb-0">
                        <strong>Phone:</strong>
                        {{ $appointment->patient_phone }}
                    </p>

                </div>


                {{-- Medical reason --}}
                <div class="border rounded-4 p-4">

                    <h5 class="black mb-3">
                        Visit Information
                    </h5>

                    <p class="mb-3">

                        <strong>
                            Examination Reason:
                        </strong>

                        <br>

                        {{ $appointment->examination_reason
                            ?: 'Not provided' }}

                    </p>


                    <p class="mb-0">

                        <strong>
                            Symptoms:
                        </strong>

                        <br>

                        {{ $appointment->symptoms
                            ?: 'Not provided' }}

                    </p>

                </div>


                {{-- Actions will be added later --}}
                @if (
                    $appointment->status === 'Pending' ||
                    $appointment->status === 'Confirmed'
                )

                    <div class="mt-4">

                        <small class="text-muted">
                            Reschedule and cancellation options
                            will be available here.
                        </small>

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection