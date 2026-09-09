<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Http\Controllers\Admin\Concerns\SortsListings;
use App\Models\City;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    use SortsListings;
    use LogsAdminActions;

    /**
     * Extensions accepted for a doctor photo.
     *
     * Checked against the extension PHP guesses from the file contents,
     * never against the name the browser sent. A file called "evil.php"
     * that starts with a GIF header passes the "image" validation rule,
     * and storing it under its original extension inside the web-served
     * public/upload/doctors folder would make it executable.
     */
    private const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Random, collision-free, extension derived from the file contents. */
    private function storeDoctorPhoto($file): string
    {
        $extension = strtolower((string) $file->extension());

        if (!in_array($extension, self::PHOTO_EXTENSIONS, true)) {
            abort(422, 'Unsupported image type.');
        }

        // time() alone collides when two photos are uploaded in the same second
        $name = Str::uuid() . '.' . $extension;
        $file->move(public_path('upload/doctors'), $name);

        return $name;
    }

    public function dashboard()
    {
        // Accounts, split by the account_status the admin can toggle
        $accounts = [
            'doctors_total'    => User::where('user_type', 'Doctor')->count(),
            'doctors_active'   => User::where('user_type', 'Doctor')->where('account_status', 'Active')->count(),
            'patients_total'   => User::where('user_type', 'Patient')->count(),
            'patients_active'  => User::where('user_type', 'Patient')->where('account_status', 'Active')->count(),
            'doctors_no_profile' => User::where('user_type', 'Doctor')
                ->whereDoesntHave('doctorDetails')->count(),
        ];

        // One grouped query instead of six counts
        $byStatus = DB::table('appointments')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $appointments = [
            'total'     => (int) $byStatus->sum(),
            'pending'   => (int) ($byStatus['Pending'] ?? 0),
            'confirmed' => (int) ($byStatus['Confirmed'] ?? 0),
            'completed' => (int) ($byStatus['Completed'] ?? 0),
            'cancelled' => (int) ($byStatus['Cancelled'] ?? 0),
            'rejected'  => (int) ($byStatus['Rejected'] ?? 0),
            'no_show'   => (int) ($byStatus['NoShow'] ?? 0),
            'today'     => DB::table('appointments')->whereDate('appointment_date', now()->toDateString())->count(),
        ];

        // Master data the booking flow depends on; a zero here breaks patient search
        $masterData = [
            'cities'          => DB::table('cities')->where('status', 'Active')->count(),
            'facilities'      => DB::table('facilities')->where('status', 'Active')->count(),
            'specializations' => DB::table('specializations')->where('status', 'Active')->count(),
            'assignments'     => DB::table('doctor_assignments')->where('status', 'Active')->count(),
            'open_slots'      => DB::table('doctor_appointment_slots')->where('status', 'Available')->count(),
        ];

        $newContactMessages = DB::table('contact_messages')->where('status', 'New')->count();

        // Queue the admin actually has to act on
        $pendingQueue = DB::table('appointments')
            ->where('status', 'Pending')
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get(['appointment_number', 'patient_name', 'appointment_date', 'start_time']);

        return view('admin.AdminDashboard', compact(
            'accounts',
            'appointments',
            'masterData',
            'newContactMessages',
            'pendingQueue'
        ));
    }

    // ============= Account Control =============

    /**
     * Accounts are never hard-deleted, they are deactivated.
     *
     * Deleting a user row cascades through doctors -> doctor_assignments ->
     * schedules and slots, and nulls the doctor and patient references on
     * appointments, appointment_histories, medical_contents and
     * contact_messages. For a clinic system that means losing the record of
     * who treated whom, so account_status is used instead: the account stays
     * intact and AuthController refuses the login.
     */
    public function toggleAccountStatus(Request $req, $id)
    {
        $user = User::findOrFail($id);

        // An admin must not be able to lock themselves out
        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->account_status = $user->account_status === 'Active' ? 'Inactive' : 'Active';
        $user->save();

        if ($user->account_status === 'Inactive') {
            // Drop any open session so the user is signed out immediately
            // (only has an effect when SESSION_DRIVER=database).
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $state = $user->account_status === 'Active' ? 'reactivated' : 'deactivated';

        $this->logAdminAction('account.' . $state, [
            'user_id'   => $user->id,
            'user_type' => $user->user_type,
            'email'     => $user->email,
        ]);

        return back()->with('success', 'Account of ' . $user->name . ' has been ' . $state . '.');
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address'  => ['nullable', 'string', 'max:500'],
            'city_id'  => ['nullable', 'exists:cities,id'],
        ], [
            'name.required'     => 'Full name is required.',
            'email.unique'      => 'This email is already in use.',
            'number.digits'     => 'Phone number must be exactly 10 digits.',
            'password.min'       => 'Password must be at least 8 characters.',
            'password.confirmed' => 'The two passwords do not match.',
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

    public function doctorsList(Request $req)
    {
        $query = User::with(['doctorDetails:id,user_id,status,expertise'])
            ->where('user_type', 'Doctor');

        if ($req->filled('keyword')) {
            $keyword = $req->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('number', 'like', '%' . $keyword . '%');
            });
        }

        if ($req->filled('account_status')) {
            $query->where('account_status', $req->account_status);
        }

        // Doctors whose professional profile is still missing
        if ($req->input('profile') === 'missing') {
            $query->whereDoesntHave('doctorDetails');
        } elseif ($req->filled('profile')) {
            $query->whereHas('doctorDetails', fn($q) => $q->where('status', $req->profile));
        }

        $this->applySort($query, $req, ['name', 'email', 'number', 'account_status'], 'name');

        $doctors = $query->paginate($this->perPage($req))->withQueryString();

        return view('admin.AdminDoctors', compact('doctors'));
    }

    public function getThisDoctorProfile($id)
    {
        // Guard the user type: without it, passing a patient id here leads
        // straight to the "add professional profile" form and would attach
        // a doctors row to a patient account.
        $user = User::where('user_type', 'Doctor')->findOrFail($id);

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
        $validated = $req->validate([
            'id' => ['required', 'integer', 'exists:doctors,id'],
        ]);

        $doctor = Doctor::with('user:id,name')->findOrFail($validated['id']);

        $doctor->status = $doctor->status === 'Active' ? 'Inactive' : 'Active';
        $doctor->save();

        $visibility = $doctor->status === 'Active'
            ? 'is now visible in patient search'
            : 'is now hidden from patient search';

        $this->logAdminAction('doctor.visibility.' . strtolower($doctor->status), [
            'doctor_id' => $doctor->id,
            'user_id'   => $doctor->user_id,
        ]);

        // The name is read from the database, not from the posted form, so a
        // tampered field cannot inject text into the message shown to the admin.
        $name = $doctor->user->name ?? 'This doctor';

        // Triggered from the doctor detail page, so stay on it
        return back()->with('success', $name . ' ' . $visibility . '.');
    }

    public function getAddDoctorDetailsFormData($id)
    {
        $doctor = User::where('user_type', 'Doctor')->findOrFail($id);

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
            'image'          => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
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

        $name = $this->storeDoctorPhoto($req->file('image'));

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
        $user   = User::where('user_type', 'Doctor')->findOrFail($id);
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
            'image'          => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
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
            $doctor->image = $this->storeDoctorPhoto($req->file('image'));
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
