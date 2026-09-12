<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    protected $table = 'doctor_schedules';

    /**
     * In the MediConnect schema a schedule belongs to a doctor_assignment
     * (doctor at a given facility and specialization), not directly to a doctor.
     */
    protected $fillable = [
        'doctor_assignment_id',
        'day',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'valid_from',
        'valid_until',
        'is_available',
    ];

    protected $casts = [
        // Form fields are submitted as strings, but Carbon::addMinutes() only
        // accepts a numeric value. Cast this attribute whenever it is read.
        'slot_duration_minutes' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_available' => 'boolean',
    ];

    // Khớp với DEFAULT của cột DB: nếu không truyền is_available khi create(),
    // model in-memory phải mặc định true, nếu không generateSlots() sẽ thoát sớm.
    protected $attributes = [
        'is_available' => true,
    ];

    public function doctorAssignment()
    {
        return $this->belongsTo(DoctorAssignment::class, 'doctor_assignment_id');
    }

    public function slots()
    {
        return $this->hasMany(
            DoctorAppointmentSlot::class,
            'doctor_schedule_id'
        );
    }
    public function exceptions()
    {
        return $this->hasMany(DoctorScheduleException::class, 'doctor_assignment_id', 'doctor_assignment_id');
    }

    // Sinh các DoctorAppointmentSlot còn thiếu cho khoảng ngày hợp lệ của lịch này,
    // dựa trên slot_duration_minutes. Dùng firstOrCreate để không tạo trùng slot đã có.
    public function generateSlots(int $daysAhead = 60): void
    {
        if (!$this->is_available) {
            return;
        }

        $slotDuration = (int) $this->slot_duration_minutes;
        if ($slotDuration < 1 || !$this->start_time || !$this->end_time) {
            return;
        }

        $rangeStart = $this->valid_from ? $this->valid_from->copy() : today();
        if ($rangeStart->lt(today())) {
            $rangeStart = today();
        }

        $rangeEnd = $this->valid_until
            ? $this->valid_until->copy()
            : today()->addDays($daysAhead);

        if ($rangeEnd->lt($rangeStart)) {
            return;
        }

        $weekdays = [
            'Sunday', 'Monday', 'Tuesday', 'Wednesday',
            'Thursday', 'Friday', 'Saturday',
        ];
        $dayIndex = array_search($this->day, $weekdays);

        for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
            if ((int) $date->dayOfWeek !== $dayIndex) {
                continue;
            }

            try {
                $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $this->start_time);
                $dayEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $this->end_time);
            } catch (\Throwable $e) {
                return;
            }

            if (!$slotStart || !$dayEnd || $dayEnd->lte($slotStart)) {
                continue;
            }

            // A closed exception removes the whole day. An open exception
            // replaces the normal hours for that assignment/date.
            $exceptions = DoctorScheduleException::where('doctor_assignment_id', $this->doctor_assignment_id)
                ->whereDate('exception_date', $date->format('Y-m-d'))
                ->get();
            $closedDay = $exceptions->first(fn ($exception) => !$exception->is_available && !$exception->start_time && !$exception->end_time);
            if ($closedDay) {
                continue;
            }
            $openException = $exceptions->first(fn ($exception) => $exception->is_available && $exception->start_time && $exception->end_time);
            if ($openException) {
                try {
                    $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $openException->start_time);
                    $dayEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $openException->end_time);
                } catch (\Throwable $e) {
                    continue;
                }
            }

            // Do not create a partial slot. This also protects against
            // malformed schedules that could otherwise loop forever.
            if ($dayEnd->lte($slotStart) || $slotStart->diffInMinutes($dayEnd) % $slotDuration !== 0) {
                continue;
            }

            while ($slotStart->copy()->addMinutes($slotDuration)->lte($dayEnd)) {
                $slotEnd = $slotStart->copy()->addMinutes($slotDuration);

                $blocked = $exceptions->first(function ($exception) use ($slotStart, $slotEnd, $date) {
                    if ($exception->is_available || !$exception->start_time || !$exception->end_time) {
                        return false;
                    }

                    $exceptionStart = Carbon::parse($date->format('Y-m-d') . ' ' . $exception->start_time);
                    $exceptionEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $exception->end_time);

                    return $slotStart->lt($exceptionEnd) && $slotEnd->gt($exceptionStart);
                });

                // Tra cứu bằng whereDate() thay vì so khớp chuỗi trực tiếp.
                $exists = DoctorAppointmentSlot::where('doctor_assignment_id', $this->doctor_assignment_id)
                    ->whereDate('slot_date', $date->format('Y-m-d'))
                    ->where('start_time', $slotStart->format('H:i:s'))
                    ->exists();

                if (!$blocked && !$exists && $slotStart->gt(now())) {
                    DoctorAppointmentSlot::create([
                        'doctor_assignment_id' => $this->doctor_assignment_id,
                        'slot_date' => $date->format('Y-m-d'),
                        'start_time' => $slotStart->format('H:i:s'),
                        'doctor_schedule_id' => $this->id,
                        'end_time' => $slotEnd->format('H:i:s'),
                        'status' => 'Available',
                    ]);
                }


                $slotStart = $slotEnd;
            }
        }
    }
}
