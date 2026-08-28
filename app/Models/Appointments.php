<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Doctor;
use App\Models\User;

class Appointments extends Model
{
    protected $table = "appointments";
    protected $fillable = [
        'user_id',
        'doctor_id',
        'name',
        'number',
        'day',
        'date',
        'time',
        'message',
        'status',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function patient()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
