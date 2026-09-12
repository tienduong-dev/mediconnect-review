<?php

namespace App\Http\Controllers;

use App\Models\Appointments;
use App\Models\City;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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
        $req->validate([
            'id' => 'required|integer',
            'status' => 'required|in:Confirmed,Rejected,Cancelled,Completed,NoShow',
        ]);

        $doctor = Doctor::where('user_id', Auth::id())
            ->where('status', 'Active')
            ->firstOrFail();

        // Chỉ cho phép bác sĩ cập nhật appointment thuộc chính mình
        $appointment = Appointments::whereHas(
            'doctorAssignment',
            function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            }
        )->findOrFail($req->id);

        $oldStatus = $appointment->status;
        $appointment->status = $req->status;

        if ($req->status === 'Confirmed') {
            $appointment->confirmed_at = now();
        } elseif ($req->status === 'Completed') {
            $appointment->completed_at = now();
        } elseif ($req->status === 'NoShow') {
            $appointment->no_show_at = now();
        }

        $appointment->save();

        \App\Models\AppointmentHistory::create([
            'appointment_id' => $appointment->id,
            'changed_by_user_id' => Auth::id(),
            'action' => $req->status,
            'old_status' => $oldStatus,
            'new_status' => $req->status,
            'old_doctor_assignment_id' => $appointment->doctor_assignment_id,
            'new_doctor_assignment_id' => $appointment->doctor_assignment_id,
            'old_slot_id' => $appointment->slot_id,
            'new_slot_id' => $appointment->slot_id,
            'note' => 'Appointment status updated by doctor.',
        ]);

        return redirect('Doctor/DoctorDashboard')->with('infoSave', 'Appointment status updated successfully.');
    }

    public function doctorCollectDataForm()
    {
        $cities = City::where('status', 'Active')->orderBy('name')->get();
        return view('doctor.DoctorDetailsForm', compact('cities'));
    }

    public function saveDoctorDetails(Request $req)
    {
        // echo "<pre>";
        // print_r($req->all());
        // die;

        $req->validate([
            'image' => ['required', 'image', 'max:4096'],
            'expertise' => 'required',
            'experience' => 'required|numeric',
            'education' => 'required',
            'profession' => 'required',
            'days' => 'required|array|min:1',
            'city_id' => ['nullable', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('status', 'Active'))],
            'slot_duration_minutes' => 'nullable|integer|min:5|max:480',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s'],
        ]);
        $this->validateScheduleWindow($req);

        $file = $req->image;
        $name = time() . "." . $file->getClientOriginalExtension();
        $file->move(public_path('upload/doctors'), $name); // move file on upload folder

        // Luôn dùng user đang đăng nhập, không tin user_id do client gửi lên
        User::where('id', Auth::id())->update(['city_id' => $req->city_id]);

        $doctor = Doctor::create([
            'image' => $name,
            'user_id' => Auth::id(),
            'expertise' => $req->expertise,
            'experience' => $req->experience,
            'education' => $req->education,
            'profession' => $req->profession,
        ]);

        // Schema mới yêu cầu lịch làm việc gắn với doctor_assignment_id,
        // nên phải lấy (hoặc tự tạo) 1 assignment mặc định cho bác sĩ này
        try {
            $assignment = $doctor->firstOrCreateAssignment();
        } catch (\RuntimeException $e) {
            return redirect('Doctor/DoctorDashboard')->with('infoError', $e->getMessage());
        }

        foreach ($req->days as $day) {
            $schedule = DoctorSchedule::create([
                'doctor_assignment_id' => $assignment->id,
                'day' => $day,
                'start_time' => $req->start_time,
                'end_time' => $req->end_time,
                'slot_duration_minutes' => $req->slot_duration_minutes ?: 30,
                'valid_from' => $req->valid_from,
                'valid_until' => $req->valid_until,
            ]);
            $schedule->generateSlots();
        }

        return redirect('Doctor/DoctorDashboard')->with('infoSave', 'Your information saved successfully');
    }

    public function getDoctorProfile()
    {
        $user = Auth::user();
        $doctor = Doctor::with('schedules')
            ->where('user_id', $user->id)
            ->where('status', 'Active')
            ->firstOrFail();
        // echo "<pre>";
        // print_r($doctor->toArray());
        // die;


        return view('doctor.DoctorProfile', compact('user', 'doctor'));
    }

    public function getEditDoctorForm($id)
    {
        // Chỉ cho phép bác sĩ xem/sửa đúng hồ sơ của chính mình
        abort_unless((int) $id === Auth::id(), 403);
        abort_unless(Doctor::where('user_id', Auth::id())->where('status', 'Active')->exists(), 403);

        $user = User::find($id);
        $doctor = Doctor::with('schedules')->where('user_id', $id)->first();
        $cities = City::where('status', 'Active')->orderBy('name')->get();
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
        return view('doctor.DoctorEditProfileForm', compact('user', 'doctor', 'days', 'cities'));
    }

    public function saveEditedDoctorDetails(Request $req)
    {
        // echo "<pre>";
        // print_r($req->all());
        // die;

        abort_unless(Doctor::where('user_id', Auth::id())->where('status', 'Active')->exists(), 403);

        $req->validate([
            'city_id' => ['nullable', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('status', 'Active'))],
            'slot_duration_minutes' => 'nullable|integer|min:5|max:480',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'days' => 'required|array|min:1',
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s'],
        ]);
        $this->validateScheduleWindow($req);

        if ($req->hasFile('image')) {
            $file = $req->image;
            $name = time() . "." . $file->getClientOriginalExtension();
            $file->move(public_path('upload/doctors'), $name);
        }

        // Luôn thao tác trên hồ sơ của bác sĩ đang đăng nhập, bỏ qua user_id/id do client gửi lên
        $user = User::find(Auth::id());

        $user->name = $req->name;
        $user->email = $req->email;
        $user->number = $req->number;
        $user->city_id = $req->city_id;

        $user->save();

        $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();

        $doctor->expertise = $req->expertise;
        $doctor->experience = $req->experience;
        $doctor->education = $req->education;
        $doctor->profession = $req->profession;

        $doctor->save();

        // Xóa lịch cũ theo doctor_assignment_id (cột doctor_id không còn tồn tại)
        try {
            $assignment = $doctor->firstOrCreateAssignment();
        } catch (\RuntimeException $e) {
            return redirect('Doctor/DoctorDashboard')->with('infoError', $e->getMessage());
        }

        // Chỉ xóa các slot Available trong tương lai, giữ nguyên slot đã đặt
        $assignment->appointmentSlots()
            ->where('status', 'Available')
            ->whereDate('slot_date', '>=', today())
            ->delete();
        DoctorSchedule::where('doctor_assignment_id', $assignment->id)->delete();

        // echo "<pre>";
        // print_r($req->days);
        // die;
        foreach ($req->days as $day) {
            $schedule = DoctorSchedule::create([
                'doctor_assignment_id' => $assignment->id,
                'day' => $day,
                'start_time' => $req->start_time,
                'end_time' => $req->end_time,
                'slot_duration_minutes' => $req->slot_duration_minutes ?: 30,
                'valid_from' => $req->valid_from,
                'valid_until' => $req->valid_until,
            ]);
            $schedule->generateSlots();
        }

        return redirect('Doctor/DoctorDashboard')->with('infoSave', 'Your edited information saved successfully');
    }

    public function deleteDoctor($id)
    {
        // Chỉ cho phép bác sĩ xóa đúng tài khoản của chính mình
        abort_unless((int) $id === Auth::id(), 403);
        abort_unless(Doctor::where('user_id', Auth::id())->where('status', 'Active')->exists(), 403);

        User::find($id)->delete();
        Auth::logout();
        return redirect('index')->with('doctorDeletedOkay', 'Your account and all data deleted permanently!');
    }

    private function validateScheduleWindow(Request $request): void
    {
        $parse = function (string $value) {
            foreach (['H:i', 'H:i:s'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $value);
                } catch (\Throwable $e) {
                    // Try the second supported HTML/API time format.
                }
            }

            return null;
        };

        $start = $parse((string) $request->input('start_time'));
        $end = $parse((string) $request->input('end_time'));
        if (!$start || !$end || $end->lte($start)) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be later than start time.',
            ]);
        }

        $duration = (int) ($request->input('slot_duration_minutes') ?: 30);
        $minutes = $start->diffInMinutes($end);
        if ($duration < 1 || $minutes % $duration !== 0) {
            throw ValidationException::withMessages([
                'slot_duration_minutes' => 'Slot duration must divide the working hours exactly.',
            ]);
        }

        if ($request->filled('valid_from') && Carbon::parse($request->valid_from)->lt(today())) {
            throw ValidationException::withMessages([
                'valid_from' => 'A schedule cannot start in the past.',
            ]);
        }
        if ($request->filled('valid_until') && Carbon::parse($request->valid_until)->lt(today())) {
            throw ValidationException::withMessages([
                'valid_until' => 'A schedule cannot end in the past.',
            ]);
        }
    }
}
