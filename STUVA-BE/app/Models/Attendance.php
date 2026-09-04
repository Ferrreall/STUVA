<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'permission_request_id',
        'date',
        'status',
        'notes',
    ];

    // Relasi ke User (Siswa)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Relasi ke PermissionRequest (Surat Izin)
    public function permissionRequest()
    {
        return $this->belongsTo(PermissionRequest::class, 'permission_request_id');
    }
}