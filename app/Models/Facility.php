<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $fillable = [
        'city_id',
        'name',
        'code',
        'address',
        'phone',
        'email',
        'description',
        'status',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function facilitySpecializations()
    {
        return $this->hasMany(FacilitySpecialization::class);
    }
}