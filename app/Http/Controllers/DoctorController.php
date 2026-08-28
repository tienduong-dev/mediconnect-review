<?php

namespace App\Http\Controllers;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;

class DoctorController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user(); // logged-in user 
        $doctor = Doctor::with(['schedules', 'appointment'])->where('user_id', $user->id)->first();
        // echo "<pre>";
        // echo $doctor->id;
        // print_r($doctor->appointment->toArray());
        // print_r($doctor->appointment[0]->day);
        // print_r($doctor->toArray());
        // die;

        if (!$doctor) {
            return redirect('Doctor/ShowDoctorDetailsForm');
        } else {
            return view('doctor.DoctorDashboard', compact('user', 'doctor'));
        }
    }

    public function updateAppointmentStatus(Request $req)
    {
        $appointment = Appointments::find($req->id);
        // echo "<pre>";
        // print_r($req->all());
        // print_r($appointment->toArray());
        // die;

        $appointment->status = $req->status;
        $appointment->save();
        return redirect('Doctor/DoctorDashboard');
    }

    public function doctorCollectDataForm()
    {
        return view('doctor.DoctorDetailsForm');
    }

    public function saveDoctorDetails(Request $req)
    {
        // echo "<pre>";
        // print_r($req->all());
        // die;

        $req->validate([
            'image' => 'image',
            'expertise' => 'required',
            'experience' => 'required|numeric',
            'education' => 'required',
            'profession' => 'required',
            'days' => 'required|array|min:1',
        ]);

        $file = $req->image;
        $name = time() . "." . $file->getClientOriginalExtension();
        $file->move(public_path('upload/doctors'), $name); // move file on upload folder

        $doctor = Doctor::create([
            'image' => $name,
            'user_id' => $req->user_id,
            'expertise' => $req->expertise,
            'experience' => $req->experience,
            'education' => $req->education,
            'profession' => $req->profession,
        ]);

        foreach ($req->days as $day) {
            DoctorSchedule::create([
                'doctor_id' => $doctor->id,
                'day' => $day,
                'start_time' => $req->start_time,
                'end_time' => $req->end_time
            ]);
        }

        return redirect('Doctor/DoctorDashboard')->with('infoSave', 'Your information saved successfully');
    }

    public function getDoctorProfile()
    {
        $user = Auth::user();
        $doctor = Doctor::with('schedules')->where('user_id', $user->id)->first();
        // echo "<pre>";
        // print_r($doctor->toArray());
        // die;


        return view('doctor.DoctorProfile', compact('user', 'doctor'));
    }

    public function getEditDoctorForm($id)
    {
        $user = User::find($id);
        $doctor = Doctor::with('schedules')->where('user_id', $id)->first();
        // echo "<pre>";
        // print_r($doctor->toArray());
        // print_r($doctor->schedules->toArray());
        $days[] = "";
        $i = 0;
        foreach ($doctor->schedules as $schedule) {
            $days[$i] = $schedule['day'];
            $i++;
        }
        // echo print_r($days);
        // die;
        return view('doctor.DoctorEditProfileForm', compact('user', 'doctor', 'days'));
    }

    public function saveEditedDoctorDetails(Request $req)
    {
        // echo "<pre>";
        // print_r($req->all());
        // die;

        if ($req->hasFile('image')) {
            $file = $req->image;
            $name = time() . "." . $file->getClientOriginalExtension();
            $file->move(public_path('upload/doctors'), $name);
        }

        $user = User::find($req->user_id);

        $user->name = $req->name;
        $user->email = $req->email;
        $user->number = $req->number;

        $user->save();

        $doctor = Doctor::find($req->id);

        $doctor->expertise = $req->expertise;
        $doctor->experience = $req->experience;
        $doctor->education = $req->education;
        $doctor->profession = $req->profession;

        $doctor->save();

        DoctorSchedule::where('doctor_id', $req->id)->delete(); // delete old all data for this doctor

        // echo "<pre>";
        // print_r($req->days);
        // die;
        foreach ($req->days as $day) {
            DoctorSchedule::create([
                'doctor_id' => $req->id,
                'day' => $day,
                'start_time' => $req->start_time,
                'end_time' => $req->end_time
            ]);
        }

        return redirect('Doctor/DoctorDashboard')->with('infoSave', 'Your edited information saved successfully');
    }

    public function deleteDoctor($id)
    {
        User::find($id)->delete();
        Auth::logout();
        return redirect('index')->with('doctorDeletedOkay', 'Your account and all data deleted permanently!');
    }
}
