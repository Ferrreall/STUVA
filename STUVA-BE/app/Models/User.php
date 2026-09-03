<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'class_name',
        'student_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relasi jika user ini adalah Ortu (mengambil data anak/siswa)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}