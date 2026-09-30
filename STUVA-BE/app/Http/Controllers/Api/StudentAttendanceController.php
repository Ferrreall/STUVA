<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

        // Menghitung ringkasan akumulasi kehadiran        // 1 query → grup per status
        $counts = Attendance::where('student_id', $user->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'hadir' => $counts['hadir'] ?? 0,
            'sakit' => $counts['sakit'] ?? 0,
            'izin'  => $counts['izin'] ?? 0,
            'alpha' => $counts['alpha'] ?? 0,
            'dispen'=> $counts['dispen'] ?? 0,
        ];

        return response()->json([
            'status'  => 'success',
            'summary' => $summary,
            'data'    => $history,
        ]);
    }

    // Status absen hari ini (dipanggil FE saat buka halaman)
public function absenStatus(Request $request)
{
    $student = $request->user();
    $today = Carbon::today('Asia/Jakarta')->toDateString();

    $existing = Attendance::where('student_id', $student->id)
        ->where('date', $today)
        ->first();

    return response()->json([
        'status' => 'success',
        'data' => [
            'check_in'  => $existing->check_in ?? null,
            'check_out' => $existing->check_out ?? null,
            'can_check_in'  => !$existing,
            'can_check_out' => $existing && !$existing->check_out,
            'done'          => $existing && $existing->check_out,
        ]
    ]);
}

// Absen masuk / pulang (dengan verifikasi GPS)
public function absen(Request $request)
{
    $request->validate([
        'latitude'  => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    $student = $request->user();
    $today = Carbon::today('Asia/Jakarta')->toDateString();

    $existing = Attendance::where('student_id', $student->id)
        ->where('date', $today)
        ->first();

    // sudah lengkap?
    if ($existing && $existing->check_in && $existing->check_out) {
        return response()->json([
            'status' => 'error',
            'message' => 'Kamu sudah absen masuk & pulang hari ini.'
        ], 422);
    }

    // verifikasi jarak dari sekolah (haversine)
    $schoolLat = config('sekolah.latitude');
    $schoolLng = config('sekolah.longitude');
    $radius    = config('sekolah.radius_meters');

    $distance = $this->haversineMeters(
        (float) $request->latitude, (float) $request->longitude,
        (float) $schoolLat, (float) $schoolLng
    );

        // ★ DEBUG — hapus setelah beres
    Log::info('=== ABSEN DEBUG ===', [
        'device'  => [$request->latitude, $request->longitude],
        'sekolah' => [$schoolLat, $schoolLng],
        'radius'  => $radius,
        'jarak_m' => round($distance),
    ]);

    if ($distance > $radius) {
        return response()->json([
            'status' => 'error',
            'message' => 'Kamu berada ' . round($distance) . ' m dari sekolah. Maksimal ' . $radius . ' m.',
            'data' => ['distance_m' => round($distance)]
        ], 422);
    }

    // ABSEN MASUK
    if (!$existing) {
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'date'       => $today,
            'status'     => 'hadir',
            'check_in'   => now('Asia/Jakarta')->format('H:i:s'),
            'method'     => 'gps',
            'entry_type' => 'system',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absen masuk berhasil! Jam ' . $attendance->check_in,
            'data' => $attendance
        ], 201);
    }

    // ABSEN PULANG
    $existing->update([
        'check_out' => now('Asia/Jakarta')->format('H:i:s'),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Absen pulang berhasil! Jam ' . $existing->check_out,
        'data' => $existing
    ]);
}

// jarak dua koordinat (meter)
private function haversineMeters($lat1, $lng1, $lat2, $lng2)
{
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
}