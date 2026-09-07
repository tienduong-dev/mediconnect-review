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
        'valid_from'   => 'date',
        'valid_until'  => 'date',
        'is_available' => 'boolean',
    ];

    public function assignment()
    {
        return $this->belongsTo(
            DoctorAssignment::class,
            'doctor_assignment_id'
        );
    }

    public function slots()
    {
        return $this->hasMany(
            DoctorAppointmentSlot::class,
            'doctor_schedule_id'
        );
    }
}
