<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    protected $table = 'doctor_schedules';

    /**
     * Theo schema MediConnect moi: lich lam viec gan voi doctor_assignments
     * (bac si lam tai co so + chuyen khoa nao), khong gan truc tiep voi doctors.
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
