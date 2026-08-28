<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DoctorSchedule;
use App\Models\Appointments;

class Doctor extends Model
{
    protected $table = "doctors";
    protected $fillable = ['image', 'user_id', 'expertise', 'experience', 'education', 'profession', 'status'];

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }
    public function appointment()
    {
        return $this->hasMany(Appointments::class, 'doctor_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
