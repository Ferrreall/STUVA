<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\PermissionRequest;
use Carbon\Carbon;

class GenerateDailyAttendance extends Command
{
    protected $signature = 'attendance:generate-daily';
    protected $description = 'Otomatis generate data presensi harian siswa jam 11 siang (Senin-Jumat)';

    public function handle()
    {
        $today = Carbon::today()->toDateString();

        // Ambil semua siswa
        $students = User::where('role', 'siswa')->get();

        foreach ($students as $student) {
            // Cek apakah siswa sudah punya record presensi hari ini
            $existingAttendance = Attendance::where('student_id', $student->id)
                ->whereDate('date', $today)
                ->first();

            if ($existingAttendance) {
                continue; // Skip jika sudah ada record
            }

            // Cek apakah ada pengajuan izin/sakit yang approved untuk hari ini
            $permission = PermissionRequest::where('student_id', $student->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            if ($permission) {
                // Jika ada izin approved, status presensi sesuai jenis izin
                Attendance::create([
                    'student_id'            => $student->id,
                    'permission_request_id' => $permission->id,
                    'date'                  => $today,
                    'status'                => $permission->type, // 'sakit', 'izin', atau 'dispen'
                ]);
            } else {
                // Jika tidak ada izin, otomatis di-set 'hadir'
                Attendance::create([
                    'student_id'            => $student->id,
                    'permission_request_id' => null,
                    'date'                  => $today,
                    'status'                => 'hadir',
                ]);
            }
        }

        $this->info("Presensi harian tanggal {$today} berhasil dioxidasi/generated!");
    }
}