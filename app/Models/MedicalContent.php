<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalContent extends Model
{
    protected $fillable = [
        'author_id',
        'content_type',
        'title',
        'slug',
        'summary',
        'body',
        'featured_image',
        'source_url',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}