<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\City;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $total_doctor  = User::where('user_type', 'Doctor')->count();
        $total_patient = User::where('user_type', 'Patient')->count();
        $total_apt     = Appointments::count();

        return view('admin.AdminDashboard', compact('total_doctor', 'total_patient', 'total_apt'));
    }

    // ============= Appointment Control =============

    public function getAppointmentPage()
    {
        $appointments = Appointments::with(['patient', 'doctor.user'])->paginate(8);

        return view('admin.AdminAppointments', compact('appointments'));
    }

    public function updateAppointmentStatus(Request $req)
    {
        $appointment = Appointments::find($req->id);

        if (!$appointment) {
            return back()->with('error', 'Appointment not found.');
        }

        $appointment->status = $req->status;
        $appointment->save();

        return redirect('Admin/AdminDashboard');
    }

    // ============= Patient Control =============

    public function patientList()
    {
        $patients = User::where('user_type', 'Patient')->paginate(8);

        return view('admin.AdminPatients', compact('patients'));
    }

    /**
     * Do not hard-delete an account that still has live appointments, because
     * appointments.patient_user_id is ON DELETE SET NULL, which would lose the patient trail.
     */
    public function deleteThisPatient($id)
    {
        $hasLiveAppointment = DB::table('appointments')
            ->where('patient_user_id', $id)
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->exists();

        if ($hasLiveAppointment) {
            return redirect('Admin/Patients')
                ->with('error', 'Cannot delete: this patient still has pending or confirmed appointments.');
        }

        User::where('id', $id)->delete();

        return redirect('Admin/Patients')->with('success', 'Patient account deleted.');
    }

    // ============= Doctor Control =============

    public function showDoctorRegisterForm()
    {
        return view('admin.AdminDoctorRegister', [
            'cities' => City::where('status', 'Active')->orderBy('name')->get(),
        ]);
    }

    public function registerDoctor(Request $req)
    {
        $validated = $req->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'number'   => ['required', 'digits:10'],
            'password' => ['required', 'string', 'min:8'],
            'address'  => ['nullable', 'string', 'max:500'],
            'city_id'  => ['nullable', 'exists:cities,id'],
        ], [
            'name.required'     => 'Full name is required.',
            'email.unique'      => 'This email is already in use.',
            'number.digits'     => 'Phone number must be exactly 10 digits.',
            'password.min'      => 'Password must be at least 8 characters.',
        ]);

        // user_type is always Doctor on this screen; never read it from the request
        $data = User::create([
            'user_type'      => 'Doctor',
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'password'       => $validated['password'],
            'number'         => $validated['number'],
            'address'        => $validated['address'] ?? null,
            'city_id'        => $validated['city_id'] ?? null,
            'account_status' => 'Active',
        ]);

        return redirect('Admin/AdminDoctorDetailsForm/' . $data->id)
            ->with('DoctorRegisterOKay', 'Doctor account created. Please fill in the professional profile next.');
    }

    public function doctorsList()
    {
        $doctors = User::with(['doctorDetails:id,user_id,status'])
            ->where('user_type', 'Doctor')
            ->paginate(8);

        return view('admin.AdminDoctors', compact('doctors'));
    }

    public function getThisDoctorProfile($id)
    {
        $user = User::find($id);

        // Eager load assignments (facility + specialization + room + fee)
        $doctor = Doctor::with([
            'assignments.facilitySpecialization.facility.city',
            'assignments.facilitySpecialization.specialization',
        ])->where('user_id', $id)->first();

        if ($doctor == null) {
            return redirect('Admin/AdminDoctorDetailsForm/' . $id)
                ->with('DoctorDetailsNotFound', 'This doctor has no professional profile yet.');
        }

        return view('admin.AdminDoctorProfile', compact('user', 'doctor'));
    }

    public function updateDoctorStatus(Request $req)
    {
        $doctor = Doctor::find($req->id);

        if (!$doctor) {
            return redirect('Admin/Doctors')->with('error', 'Doctor profile not found.');
        }

        $doctor->status = $doctor->status === 'Active' ? 'Inactive' : 'Active';
        $doctor->save();

        return redirect('Admin/Doctors')->with('success', 'Status updated for doctor ' . $req->name . '.');
    }

    public function deleteThisDoctor($id)
    {
        $doctor = Doctor::where('user_id', $id)->first();

        if ($doctor) {
            $hasLiveAppointment = DB::table('appointments')
                ->join('doctor_assignments', 'appointments.doctor_assignment_id', '=', 'doctor_assignments.id')
                ->where('doctor_assignments.doctor_id', $doctor->id)
                ->whereIn('appointments.status', ['Pending', 'Confirmed'])
                ->exists();

            if ($hasLiveAppointment) {
                return redirect('Admin/Doctors')
                    ->with('error', 'Cannot delete: this doctor still has pending or confirmed appointments. Set the profile to Inactive instead.');
            }
        }

        User::where('id', $id)->delete();

        return redirect('Admin/Doctors')->with('success', 'Doctor account deleted.');
    }

    public function getAddDoctorDetailsFormData($id)
    {
        $doctor = User::findOrFail($id);

        return view('admin.AdminDoctorDetailsForm', compact('doctor'));
    }

    /**
     * Creates only the professional profile (doctors table).
     * Workplace, fee and room live under Admin > Doctor Assignments.
     * Working schedules hang off doctor_assignment_id and belong to the Doctor module.
     */
    public function saveDoctorDetails(Request $req)
    {
        $validated = $req->validate([
            'user_id'        => ['required', 'exists:users,id'],
            'image'          => ['required', 'image', 'max:4096'],
            'expertise'      => ['required', 'string', 'max:255'],
            'experience'     => ['required', 'integer', 'min:0', 'max:80'],
            'education'      => ['required', 'string', 'max:255'],
            'profession'     => ['required', 'string', 'max:255'],
            'qualifications' => ['nullable', 'string', 'max:2000'],
            'license_number' => ['nullable', 'string', 'max:100', 'unique:doctors,license_number'],
            'bio'            => ['nullable', 'string', 'max:2000'],
            'status'         => ['required', Rule::in(['Active', 'Inactive'])],
        ], [
            'image.required'        => 'Please choose a profile photo.',
            'expertise.required'    => 'Expertise is required.',
            'experience.max'        => 'Years of experience is out of range.',
            'license_number.unique' => 'This license number already exists.',
        ]);

        if (Doctor::where('user_id', $validated['user_id'])->exists()) {
            return redirect('Admin/DoctorProfile/' . $validated['user_id'])
                ->with('error', 'This doctor already has a professional profile.');
        }

        $file = $req->file('image');
        $name = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('upload/doctors'), $name);

        Doctor::create([
            'image'          => $name,
            'user_id'        => $validated['user_id'],
            'expertise'      => $validated['expertise'],
            'experience'     => $validated['experience'],
            'education'      => $validated['education'],
            'profession'     => $validated['profession'],
            'qualifications' => $validated['qualifications'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'bio'            => $validated['bio'] ?? null,
            'status'         => $validated['status'],
        ]);

        return redirect('Admin/DoctorProfile/' . $validated['user_id'])
            ->with('doctorDetailsAddOkay', 'Professional profile saved. Next step: assign the doctor to a facility under Doctor Assignments.');
    }

    public function getAdminEditDoctorDetailsFormData($id)
    {
        $user   = User::findOrFail($id);
        $doctor = Doctor::where('user_id', $id)->first();

        if (!$doctor) {
            return redirect('Admin/AdminDoctorDetailsForm/' . $id)
                ->with('DoctorDetailsNotFound', 'This doctor has no professional profile yet.');
        }

        $cities = City::where('status', 'Active')->orderBy('name')->get();

        return view('admin.AdminEditDoctorDetailsForm', compact('user', 'doctor', 'cities'));
    }

    public function saveThisDoctorDetails(Request $req)
    {
        $doctor = Doctor::findOrFail($req->id);

        $validated = $req->validate([
            'user_id'        => ['required', 'exists:users,id'],
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($req->user_id)],
            'number'         => ['required', 'digits:10'],
            'address'        => ['nullable', 'string', 'max:500'],
            'city_id'        => ['nullable', 'exists:cities,id'],
            'image'          => ['nullable', 'image', 'max:4096'],
            'expertise'      => ['required', 'string', 'max:255'],
            'experience'     => ['required', 'integer', 'min:0', 'max:80'],
            'education'      => ['required', 'string', 'max:255'],
            'profession'     => ['required', 'string', 'max:255'],
            'qualifications' => ['nullable', 'string', 'max:2000'],
            'license_number' => ['nullable', 'string', 'max:100', Rule::unique('doctors', 'license_number')->ignore($doctor->id)],
            'bio'            => ['nullable', 'string', 'max:2000'],
            'status'         => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->name    = $validated['name'];
        $user->email   = $validated['email'];
        $user->number  = $validated['number'];
        $user->address = $validated['address'] ?? $user->address;
        $user->city_id = $validated['city_id'] ?? $user->city_id;
        $user->save();

        // Previous bug: the file was uploaded but the new filename was never stored
        if ($req->hasFile('image')) {
            $file = $req->file('image');
            $name = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/doctors'), $name);
            $doctor->image = $name;
        }

        $doctor->expertise      = $validated['expertise'];
        $doctor->experience     = $validated['experience'];
        $doctor->education      = $validated['education'];
        $doctor->profession     = $validated['profession'];
        $doctor->qualifications = $validated['qualifications'] ?? null;
        $doctor->license_number = $validated['license_number'] ?? null;
        $doctor->bio            = $validated['bio'] ?? null;
        $doctor->status         = $validated['status'];
        $doctor->save();

        return redirect('Admin/DoctorProfile/' . $validated['user_id'])
            ->with('ThisDoctorEditedOkay', 'Doctor profile updated successfully.');
    }
}
