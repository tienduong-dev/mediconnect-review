<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\SortsListings;
use App\Models\AppointmentHistory;
use App\Models\Appointments;
use App\Models\DoctorAppointmentSlot;
use App\Models\Facility;
use App\Notifications\AppointmentAdjustedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentRejectedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin side of the appointment workflow.
 *
 * The admin owns three transitions only:
 *   Pending  -> Confirmed   (confirm)
 *   Pending  -> Rejected    (reject)
 *   Pending/Confirmed       (adjust: move to another slot or doctor)
 *
 * Completed and NoShow belong to the doctor after the visit, so they are not
 * offered here. Every transition runs in a transaction that also updates the
 * slot, writes an appointment_histories row and notifies the people involved,
 * because those four things must never drift apart.
 */
class AppointmentController extends Controller
{
    use SortsListings;

    private const ADMIN_ACTIONABLE = ['Pending', 'Confirmed'];

    public function index(Request $req)
    {
        $query = Appointments::with([
            'patient:id,name,email',
            'doctorAssignment.doctor.user:id,name',
            'facilitySpecialization.facility:id,name,city_id',
            'facilitySpecialization.specialization:id,name',
        ]);

        if ($req->filled('keyword')) {
            $keyword = $req->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('appointment_number', 'like', '%' . $keyword . '%')
                    ->orWhere('patient_name', 'like', '%' . $keyword . '%')
                    ->orWhere('patient_phone', 'like', '%' . $keyword . '%');
            });
        }

        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }

        if ($req->filled('facility_id')) {
            $query->whereHas(
                'facilitySpecialization',
                fn($q) => $q->where('facility_id', $req->facility_id)
            );
        }

        if ($req->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $req->date_from);
        }

        if ($req->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $req->date_to);
        }

        $this->applySort(
            $query,
            $req,
            ['appointment_number', 'patient_name', 'appointment_date', 'status'],
            'appointment_date',
            'desc'
        );

        return view('admin.appointments.index', [
            'appointments' => $query->paginate($this->perPage($req))->withQueryString(),
            'facilities'   => Facility::orderBy('name')->get(),
            'statuses'     => ['Pending', 'Confirmed', 'Rejected', 'Cancelled', 'Completed', 'NoShow'],
        ]);
    }

    public function show($id)
    {
        $appointment = Appointments::with([
            'patient',
            'doctorAssignment.doctor.user',
            'facilitySpecialization.facility.city',
            'facilitySpecialization.specialization',
            'slot',
            'histories.changedBy:id,name',
        ])->findOrFail($id);

        return view('admin.appointments.show', compact('appointment'));
    }

    public function confirm(Request $req, $id)
    {
        $appointment = Appointments::findOrFail($id);

        if ($appointment->status !== 'Pending') {
            return back()->with('error', 'Only a pending appointment can be confirmed.');
        }

        DB::transaction(function () use ($appointment, $req) {
            $oldStatus = $appointment->status;

            // Lock the slot so two admins cannot confirm the same one at once
            if ($appointment->slot_id) {
                $slot = DoctorAppointmentSlot::whereKey($appointment->slot_id)->lockForUpdate()->first();

                if ($slot) {
                    $slot->status    = 'Booked';
                    $slot->booked_at = now();
                    $slot->save();
                }
            }

            $appointment->status       = 'Confirmed';
            $appointment->confirmed_at = now();
            $appointment->save();

            $this->recordHistory($appointment, 'Confirmed', $oldStatus, $req->input('note'));
            $this->notifyParties($appointment, new AppointmentConfirmedNotification($appointment));
        });

        return back()->with('success', 'Appointment ' . $appointment->appointment_number . ' confirmed.');
    }

    public function reject(Request $req, $id)
    {
        $appointment = Appointments::findOrFail($id);

        if ($appointment->status !== 'Pending') {
            return back()->with('error', 'Only a pending appointment can be rejected.');
        }

        $data = $req->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Please explain why the request is rejected.',
        ]);

        DB::transaction(function () use ($appointment, $data) {
            $oldStatus = $appointment->status;

            // The slot goes back on sale
            if ($appointment->slot_id) {
                $slot = DoctorAppointmentSlot::whereKey($appointment->slot_id)->lockForUpdate()->first();

                if ($slot) {
                    $slot->status    = 'Available';
                    $slot->held_at   = null;
                    $slot->booked_at = null;
                    $slot->save();
                }
            }

            $appointment->status           = 'Rejected';
            $appointment->rejection_reason = $data['rejection_reason'];
            $appointment->save();

            $this->recordHistory($appointment, 'Rejected', $oldStatus, $data['rejection_reason']);
            $this->notifyPatient($appointment, new AppointmentRejectedNotification($appointment, $data['rejection_reason']));
        });

        return redirect('Admin/Appointments/Show/' . $appointment->id)
            ->with('success', 'Appointment ' . $appointment->appointment_number . ' rejected.');
    }

    public function adjustForm($id)
    {
        $appointment = Appointments::with([
            'doctorAssignment.doctor.user',
            'facilitySpecialization.facility',
            'facilitySpecialization.specialization',
            'slot',
        ])->findOrFail($id);

        if (!in_array($appointment->status, self::ADMIN_ACTIONABLE, true)) {
            return redirect('Admin/Appointments/Show/' . $appointment->id)
                ->with('error', 'Only pending or confirmed appointments can be adjusted.');
        }

        return view('admin.appointments.adjust', [
            'appointment' => $appointment,
            'slots'       => $this->selectableSlots($appointment),
        ]);
    }

    /**
     * Moving an appointment means: release the old slot, take the new one, and
     * follow the doctor that owns the new slot. Both slots are locked so a
     * patient cannot grab the target slot mid-transaction.
     */
    public function adjust(Request $req, $id)
    {
        $appointment = Appointments::findOrFail($id);

        if (!in_array($appointment->status, self::ADMIN_ACTIONABLE, true)) {
            return back()->with('error', 'Only pending or confirmed appointments can be adjusted.');
        }

        $data = $req->validate([
            'slot_id' => ['required', 'exists:doctor_appointment_slots,id'],
            'note'    => ['nullable', 'string', 'max:500'],
        ], [
            'slot_id.required' => 'Please choose the new time slot.',
        ]);

        $error = null;

        DB::transaction(function () use ($appointment, $data, &$error) {
            $newSlot = DoctorAppointmentSlot::whereKey($data['slot_id'])->lockForUpdate()->first();

            if (!$newSlot) {
                $error = 'The selected slot no longer exists.';
                return;
            }

            $isSameSlot = (int) $newSlot->id === (int) $appointment->slot_id;

            if (!$isSameSlot && $newSlot->status !== 'Available') {
                $error = 'That slot was just taken by someone else. Please pick another one.';
                return;
            }

            $oldStatus     = $appointment->status;
            $oldSlotId     = $appointment->slot_id;
            $oldAssignment = $appointment->doctor_assignment_id;

            // Release the previous slot
            if ($oldSlotId && !$isSameSlot) {
                $oldSlot = DoctorAppointmentSlot::whereKey($oldSlotId)->lockForUpdate()->first();

                if ($oldSlot) {
                    $oldSlot->status    = 'Available';
                    $oldSlot->held_at   = null;
                    $oldSlot->booked_at = null;
                    $oldSlot->save();
                }
            }

            // A confirmed appointment keeps the slot booked; a pending one only holds it
            if ($oldStatus === 'Confirmed') {
                $newSlot->status    = 'Booked';
                $newSlot->booked_at = now();
                $newSlot->held_at   = null;
            } else {
                $newSlot->status    = 'Held';
                $newSlot->held_at   = now();
                $newSlot->booked_at = null;
            }
            $newSlot->save();

            $assignment = $newSlot->doctorAssignment;

            $appointment->slot_id                    = $newSlot->id;
            $appointment->doctor_assignment_id       = $newSlot->doctor_assignment_id;
            $appointment->facility_specialization_id = $assignment->facility_specialization_id;
            $appointment->appointment_date           = $newSlot->slot_date;
            $appointment->start_time                 = $newSlot->start_time;
            $appointment->end_time                   = $newSlot->end_time;
            $appointment->save();

            $this->recordHistory(
                $appointment,
                'Adjusted',
                $oldStatus,
                $data['note'] ?? null,
                $oldAssignment,
                $oldSlotId
            );

            $notification = new AppointmentAdjustedNotification($appointment, $data['note'] ?? null);
            $this->notifyParties($appointment, $notification);

            // The doctor who lost the appointment should know too
            if ($oldAssignment && $oldAssignment !== $appointment->doctor_assignment_id) {
                $previousDoctor = optional(
                    \App\Models\DoctorAssignment::with('doctor.user')->find($oldAssignment)
                );

                $user = $previousDoctor->doctor->user ?? null;

                if ($user) {
                    $user->notify($notification);
                }
            }
        });

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        return redirect('Admin/Appointments/Show/' . $appointment->id)
            ->with('success', 'Appointment ' . $appointment->appointment_number . ' adjusted.');
    }

    /**
     * Slots the admin may move this appointment to: still open, in the future,
     * and belonging to the same facility and specialization. The current slot
     * stays in the list so the form can show what is selected now.
     */
    private function selectableSlots(Appointments $appointment)
    {
        return DoctorAppointmentSlot::with('doctorAssignment.doctor.user')
            ->whereHas(
                'doctorAssignment',
                fn($q) => $q
                    ->where('facility_specialization_id', $appointment->facility_specialization_id)
                    ->where('status', 'Active')
            )
            ->where(function ($q) use ($appointment) {
                $q->where('status', 'Available');

                if ($appointment->slot_id) {
                    $q->orWhere('id', $appointment->slot_id);
                }
            })
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->limit(200)
            ->get();
    }

    private function recordHistory(
        Appointments $appointment,
        string $action,
        ?string $oldStatus,
        ?string $note = null,
        $oldAssignmentId = null,
        $oldSlotId = null
    ): void {
        AppointmentHistory::create([
            'appointment_id'           => $appointment->id,
            'changed_by_user_id'       => auth()->id(),
            'action'                   => $action,
            'old_status'               => $oldStatus,
            'new_status'               => $appointment->status,
            'old_doctor_assignment_id' => $oldAssignmentId,
            'new_doctor_assignment_id' => $action === 'Adjusted' ? $appointment->doctor_assignment_id : null,
            'old_slot_id'              => $oldSlotId,
            'new_slot_id'              => $action === 'Adjusted' ? $appointment->slot_id : null,
            'note'                     => $note,
        ]);
    }

    /** Patient plus the doctor currently holding the appointment. */
    private function notifyParties(Appointments $appointment, $notification): void
    {
        $this->notifyPatient($appointment, $notification);

        $doctorUser = $appointment->doctorAssignment->doctor->user ?? null;

        if ($doctorUser) {
            $doctorUser->notify($notification);
        }
    }

    /** patient_user_id is nullable (a relative may book), so guard it. */
    private function notifyPatient(Appointments $appointment, $notification): void
    {
        if ($appointment->patient) {
            $appointment->patient->notify($notification);
        }
    }
}
