<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PermissionRequest;
use App\Models\Attendance; // Ganti sesuai nama model absensi kamu
use Illuminate\Http\Request;
use Carbon\Carbon;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $guru = $request->user();

        // 1. Ambil daftar kelas yang diampu oleh guru ini
        // Menggunakan Accessor getClassNameAttribute yang sudah ada
        $classes = $guru->class_name; 

        // Pastikan kelas berbentuk array untuk kueri SQL (whereIn)
        if (is_string($classes)) {
            $classes = [$classes];
        } elseif (!is_array($classes)) {
            $classes = [];
        }

        // 2. Ambil semua ID & data siswa yang berada di kelas-kelas guru tersebut
        $studentsQuery = User::where('role', 'siswa')
            ->whereIn('class_name', $classes);

        $totalSiswa = $studentsQuery->count();
        $studentIds = $studentsQuery->pluck('id');

        // Hari ini (WIB)
        $today = Carbon::today('Asia/Jakarta');

        // 3. Statistik Absensi Hari Ini
        // Hitung siswa yang sudah absen "masuk" / "hadir" hari ini
        $totalSiswaMasuk = Attendance::whereIn('student_id', $studentIds)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['hadir', 'masuk'])
            ->count();

        // Total siswa tidak masuk = Total Siswa dikurangi yang Masuk
        $totalSiswaTidakMasuk = max(0, $totalSiswa - $totalSiswaMasuk);

        // 4. Hitung Pengajuan Izin Hari Ini (atau yang butuh approval/pending)
        $totalPengajuanIzin = PermissionRequest::whereIn('student_id', $studentIds)
            ->whereDate('created_at', $today)
            ->count();

        // 5. Breakdown per kelas (untuk kartu "Kelas yang Diajar")
        $classesData = [];
        foreach ($classes as $className) {
            $ids = User::where('role', 'siswa')
                ->where('class_name', $className)
                ->pluck('id');

            $count = $ids->count();

            $attendances = Attendance::whereIn('student_id', $ids)
                ->whereDate('created_at', $today)
                ->get();

            $hadir  = $attendances->whereIn('status', ['hadir', 'masuk'])->count();
            $izin   = $attendances->where('status', 'izin')->count();
            $sakit  = $attendances->where('status', 'sakit')->count();
            $dispen = $attendances->where('status', 'dispen')->count();
            $alpha  = max(0, $count - ($hadir + $izin + $sakit + $dispen));

            $classesData[] = [
                'id'            => $className,
                'name'          => $className,
                'student_count' => $count,
                'hadir'         => $hadir,
                'izin'          => $izin,
                'sakit'         => $sakit,
                'alpha'         => $alpha,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                // Data Card Top Section
                'cards' => [
                    'total_siswa'             => $totalSiswa,
                    'total_siswa_masuk'       => $totalSiswaMasuk,
                    'total_siswa_tidak_masuk' => $totalSiswaTidakMasuk,
                    'total_pengajuan_izin'    => $totalPengajuanIzin,
                    'class_name' => $classes,
                    'classes_data' => $classesData,
                ]
            ]
        ]);
    }
}