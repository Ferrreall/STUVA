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
        'nisn',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone_number',
        'nip',
        'subject',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

// Accessor ini tahan banting terhadap string biasa maupun JSON array
public function getClassNameAttribute($value)
{
    if (is_null($value) || $value === '') {
        return null;
    }

    // Jika sudah berbentuk array (misal dari mutator)
    if (is_array($value)) {
        return $value;
    }

    // Coba decode JSON
    $decoded = json_decode($value, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        // Jika hasil decode masih berupa string JSON terbungkus ganda, decode sekali lagi
        if (is_string($decoded)) {
            $secondDecode = json_decode($decoded, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $secondDecode;
            }
        }
        return $decoded;
    }

    // Sanitasi UTF-8 untuk mencegah Syntax error dari karakter non-standard
    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
}

    // Relasi jika user ini adalah Ortu (mengambil data anak/siswa)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}