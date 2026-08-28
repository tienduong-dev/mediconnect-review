@extends('patient.PatientLayout')

@section('patient-content')
    <div class="container mt-5">
        <div class="appointment-wrapper position-relative d-center w-100">
            <div class="row gx-0 gy-5">
                <div class="col-lg-8">
                    <div class="section-title">
                        <h2 class="wow fadeInUp black" data-wow-delay=".3s">
                            <span class="position-relative z-1 w-100">
                                Edit Profile Info
                                <img src="{{ asset('assets/img/element/title-badge1.png') }}" style="bottom: -8px;"
                                    alt="img" class="title-badge1 d-md-block d-none w-100">
                            </span>
                        </h2>
                    </div>
                    <form action="{{ url('Patient/EditThisProfile') }}" method="post" enctype="multipart/form-data"
                        class="appointment-forms">
                        @csrf
                        <div class="row g-lg-3 g-3">
                            <input type="hidden" id="id" name="id" value="{{ $user->id }}">
                            <div class="col-lg-12">
                                <input type="text" name="name" value="{{ $user->name }}" id="name"
                                    placeholder="Your Name" required>
                            </div>
                            <div class="col-lg-12">
                                <input type="email" name="email" value="{{ $user->email }}" id="email"
                                    placeholder="Your Email" required>
                            </div>
                            <div class="col-lg-12">
                                <input type="tel" name="number" value="{{ $user->number }}" id="number"
                                    pattern="[0-9]{10}" maxlength="10"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    placeholder="Your Mobile Number" required>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit"
                                    class="common-btn box-style p2-bg w-100 text-nowrap d-inline-flex justify-content-center align-items-center gap-xxl-2 gap-2 fs18 fw-semibold white overflow-hidden rounded100 wow fadeInRight"
                                    data-wow-delay="0.8s">
                                    Save
                                    <img src="{{ asset('assets/img/icon/arrow-right-white.png') }}" alt="icon">
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
