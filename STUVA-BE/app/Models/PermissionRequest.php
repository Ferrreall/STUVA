<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'attachment',
        'status',
        'rejection_reason',
        'processed_at',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}