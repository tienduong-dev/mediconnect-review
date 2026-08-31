<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = 'doctors';

    protected $fillable = [
        'image',
        'user_id',
        'expertise',
        'experience',
        'education',
        'qualifications',
        'profession',
        'license_number',
        'bio',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function assignments()
    {
        return $this->hasMany(
            DoctorAssignment::class,
            'doctor_id'
        );
    }
}