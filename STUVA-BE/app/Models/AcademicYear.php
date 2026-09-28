<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relasi ke user yang terikat TA ini (kalau kolom academic_year_id dipakai di users)
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Helper: ambil TA yang sedang aktif (dipakai di banyak tempat)
    public static function active()
    {
        return static::where('is_active', true)->first();
    }
}