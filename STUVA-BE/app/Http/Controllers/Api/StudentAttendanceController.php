<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    /**
     * Menampilkan riwayat presensi khusus siswa yang sedang login
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'ortu') {
            // Jika user adalah orang tua, ambil student_id dari relasi
            $studentId = $user->student_id;

            if (!$studentId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Orang tua ini tidak memiliki siswa yang terhubung.'
                ], 400);
            }

            $user = $user->student; // Ambil data siswa terkait
        }

        // Ambil riwayat absen berdasarkan user_id / student_id yang login
        // Sesuaikan nama kolom foreign key di tabel attendances (misal: 'user_id' atau 'student_id')
        $history = Attendance::where('student_id', $user->id)
            ->orderBy('date', 'desc')
            ->paginate(10); 

        // Menghitung ringkasan akumulasi kehadiran
        $summary = [
            'hadir' => Attendance::where('student_id', $user->id)->where('status', 'hadir')->count(),
            'sakit' => Attendance::where('student_id', $user->id)->where('status', 'sakit')->count(),
            'izin'  => Attendance::where('student_id', $user->id)->where('status', 'izin')->count(),
            'alpha' => Attendance::where('student_id', $user->id)->where('status', 'alpha')->count(),
            'dispen' => Attendance::where('student_id', $user->id)->where('status', 'dispen')->count(),
        ];

        return response()->json([
            'status'  => 'success',
            'summary' => $summary,
            'data'    => $history,
        ]);
    }
}