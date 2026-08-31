@extends('patient.PatientLayout')

@section('patient-content')

@php

    $doctor = $assignment->doctor;

    $user = $doctor->user;

    $facilitySpecialization =
        $assignment->facilitySpecialization;

    $facility =
        $facilitySpecialization->facility;

    $specialization =
        $facilitySpecialization->specialization;

@endphp


<div class="container my-5">

    {{-- Back --}}
    <div class="mb-4">

        <a href="{{ url('/doctors') }}"
           class="text-decoration-none">

            ← Back to Doctor Search

        </a>

    </div>


    <div class="row g-4">

        {{-- Doctor main information --}}
        <div class="col-lg-8">

            <div class="bg-white rounded-4 shadow-sm p-4 h-100">

                <div class="d-flex align-items-start
                            justify-content-between
                            flex-wrap gap-3 mb-4">

                    <div>

                        <h2 class="black mb-1">
                            {{ $user->name }}
                        </h2>

                        <p class="pra mb-1">
                            {{ $doctor->profession }}
                        </p>

                        <span class="badge bg-success">
                            {{ $specialization->name }}
                        </span>

                    </div>

                </div>


                {{-- Bio --}}
                <div class="mb-4">

                    <h5 class="black mb-2">
                        About Doctor
                    </h5>

                    <p class="pra mb-0">
                        {{ $doctor->bio ?: 'No biography provided.' }}
                    </p>

                </div>


                {{-- Professional information --}}
                <div class="mb-4">

                    <h5 class="black mb-3">
                        Professional Information
                    </h5>

                    <ul class="doctor-professional">

                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Expertise
                            </span>

                            <span class="pra ms-3">
                                {{ $doctor->expertise }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Education
                            </span>

                            <span class="pra ms-3">
                                {{ $doctor->education }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Qualifications
                            </span>

                            <span class="pra ms-3">
                                {{ $doctor->qualifications
                                    ?: 'Not provided' }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Experience
                            </span>

                            <span class="pra ms-3">
                                {{ $doctor->experience }} years
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                License Number
                            </span>

                            <span class="pra ms-3">
                                {{ $doctor->license_number
                                    ?: 'Not provided' }}
                            </span>

                        </li>

                    </ul>

                </div>


                {{-- Practice information --}}
                <div>

                    <h5 class="black mb-3">
                        Practice Information
                    </h5>

                    <ul class="doctor-professional">

                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Facility
                            </span>

                            <span class="pra ms-3">
                                {{ $facility->name }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                City
                            </span>

                            <span class="pra ms-3">
                                {{ $facility->city->name }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Address
                            </span>

                            <span class="pra ms-3">
                                {{ $facility->address }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Specialization
                            </span>

                            <span class="pra ms-3">
                                {{ $specialization->name }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Room
                            </span>

                            <span class="pra ms-3">
                                {{ $assignment->room_number
                                    ?: 'Not assigned' }}
                            </span>

                        </li>


                        <li class="d-flex align-items-start">

                            <span class="names shift-colon">
                                Consultation Fee
                            </span>

                            <span class="pra ms-3 fw-semibold">
                                {{ number_format(
                                    $assignment->consultation_fee,
                                    0
                                ) }} VND
                            </span>

                        </li>

                    </ul>

                </div>

            </div>

        </div>


        {{-- Doctor card --}}
        <div class="col-lg-4">

            <div class="bg-white rounded-4 shadow-sm p-4 text-center">

                @if ($doctor->image)

                    <img
                        src="{{ asset(
                            'upload/doctors/' . $doctor->image
                        ) }}"
                        alt="{{ $user->name }}"
                        class="rounded-4 mb-3"
                        style="
                            width: 100%;
                            max-height: 320px;
                            object-fit: cover;
                        "
                    >

                @endif


                <h4 class="black mb-1">
                    {{ $user->name }}
                </h4>

                <p class="pra mb-1">
                    {{ $doctor->profession }}
                </p>

                <p class="pra mb-4">
                    {{ $specialization->name }}
                </p>


                {{-- Booking will be connected in Step 7 --}}
                <button
                    type="button"
                    class="common-btn box-style p2-bg w-100
                           d-inline-flex justify-content-center
                           align-items-center
                           fs18 fw-semibold white
                           overflow-hidden rounded100"
                    disabled
                >
                    Select Appointment Time
                </button>

                <small class="text-muted d-block mt-2">
                    Appointment slots will be available in the next step.
                </small>

            </div>

        </div>

    </div>

</div>

@endsection