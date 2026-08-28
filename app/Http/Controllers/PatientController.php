<?php

namespace App\Http\Controllers;

use App\Models\Appointments;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;

class PatientController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user(); // logged-in user 

        // return view('patient.PatientDashboard', compact('user'));
        return view('patient.PatientDashboard');
    }
    public function getAppointmentHistory()
    {
        $user = Auth::user(); // logged-in user 
        $user_with_apt = User::with('appointments.doctor.user')->find($user->id);
        // echo "<pre>";
        // print_r($user_with_apt->toArray());
        // echo $user_with_apt->appointments[0]->doctor->user->name;
        $appointments = [];
        $i = 0;
        foreach ($user_with_apt->appointments as $apt) {
            if ($apt->status == 'Cancel') {
                continue;
            }
            $appointments[$i]['id'] = $apt->id;
            $appointments[$i]['name'] = $apt->name;
            $appointments[$i]['number'] = $apt->number;
            $appointments[$i]['doctor_name'] = $apt->doctor->user->name;
            $appointments[$i]['day'] = $apt->day;
            $appointments[$i]['date'] = $apt->date;
            $appointments[$i]['time'] = $apt->time;
            $appointments[$i]['status'] = $apt->status;
            $i++;
        }
        // echo print_r($appointments);
        // die;

        return view('patient.PatientAppointmentHistory', compact('appointments'));
    }
    public function patientProfile()
    {
        $user = Auth::user();
        $user_with_apt = User::with('appointments')->find($user->id);
        // echo "<pre>";
        // echo $user_with_apt->id;
        // print_r($user_with_apt->toArray());
        // echo count($user_with_apt['appointments']);
        // die;
        return view('patient.PatientProfile', compact('user_with_apt'));
    }
    public function appointmentCancel(Request $req)
    {
        $appointment = Appointments::find($req->id);
        // echo "<pre>";
        // print_r($req->all());
        // print_r($appointment->toArray());
        // die;

        $appointment->status = $req->status;
        $appointment->save();
        return redirect('Patient/AppointmentHistory')->with('update', 'Appointment canceled!');
    }
    public function editPatientForm($id)
    {
        $user = User::find($id);
        // echo "<pre>";
        // print_r($user->toArray());
        // die;
        return view('patient.PatientEditProfile', compact('user'));
    }
    public function editPatient(Request $req)
    {
        // echo "<pre>";
        // print_r($req->all());
        // die;
        $user = User::find($req->id);
        $user->name = $req->name;
        $user->email = $req->email;
        $user->number = $req->number;

        // echo "<pre>";
        // print_r($req->toArray());
        // die;

        $user->save();
        return redirect('Patient/PatientDashboard');
    }
    public function deletePatient($id)
    {
        User::find($id)->delete();
        Auth::logout();
        return redirect('index');
    }
}
