<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'latitude',
        'longitude',
        'battery_level',
        'recorded_at',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}