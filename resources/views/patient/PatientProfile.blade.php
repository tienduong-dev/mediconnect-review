@extends('patient.PatientLayout')

@section('patient-content')
    <div class="container my-5">
        <ul class="doctor-professional">
            <li class="d-flex align-items-center">
                <span class="names shift-colon">Name</span>
                <span class="pra ms-3" style="font-weight:bold;">{{ $user_with_apt->name }}</span>
            </li>
            <li class="d-flex align-items-center">
                <span class="names shift-colon">Email</span>
                <span class="pra ms-3" style="font-weight:bold;">{{ $user_with_apt->email }}</span>
            </li>
            <li class="d-flex align-items-center">
                <span class="names shift-colon">Number</span>
                <span class="pra ms-3" style="font-weight:bold;">+91 {{ $user_with_apt->number }}</span>
            </li>
            <li class="d-flex align-items-center">
                @if (count($user_with_apt['appointments']) != 0)
                    <a href="{{ url('Patient/AppointmentHistory') }}" class="btn btn-small btn-info"
                        style="font-weight:bold;">Appointments</a>
                @endif
                <a href="{{ url('Patient/EditProfile', auth()->user()->id) }}" class="btn btn-small btn-success">
                    Edit Profile</a>
                <a href="{{ url('Patient/Delete', auth()->user()->id) }}"
                    onclick="return confirm('Are you sure? You want to delete your account?')"
                    class="btn btn-small btn-danger">Delete
                    Account</a>
            </li>
        </ul>
    </div>
@endsection
