<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

    // Cek kondisi status
    $hasCheckedIn  = $existing && !is_null($existing->check_in);
    $hasCheckedOut = $existing && !is_null($existing->check_out);

    // Boleh check-in jika belum ada record ATAU record ada tapi belum pernah check-in
    $canCheckIn  = !$existing || !$hasCheckedIn;
    
    // Boleh check-out HANYA jika sudah check-in DAN belum check-out
    $canCheckOut = $hasCheckedIn && !$hasCheckedOut;
    
    // Selesai jika sudah check-in dan check-out
    $isDone      = $hasCheckedIn && $hasCheckedOut;

    return response()->json([
        'status' => 'success',
        'data'   => [
            'check_in'      => $existing->check_in ?? null,
            'check_out'     => $existing->check_out ?? null,
            'can_check_in'  => $canCheckIn,
            'can_check_out' => $canCheckOut,
            'done'          => $isDone,
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

    // 1. Sudah lengkap? (Sudah check_in DAN check_out)
    if ($existing && $existing->check_in && $existing->check_out) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Kamu sudah absen masuk & pulang hari ini.'
        ], 422);
    }

    // 2. Verifikasi jarak dari sekolah (haversine)
    $schoolLat = config('sekolah.latitude');
    $schoolLng = config('sekolah.longitude');
    $radius    = config('sekolah.radius_meters');

    $distance = $this->haversineMeters(
        (float) $request->latitude, (float) $request->longitude,
        (float) $schoolLat, (float) $schoolLng
    );

    if ($distance > $radius) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Kamu berada ' . round($distance) . ' m dari sekolah. Maksimal ' . $radius . ' m.',
            'data'    => [
                'distance_m' => round($distance),
                'latitude'   => (float) $request->latitude,
                'longitude'  => (float) $request->longitude,
            ]
        ], 422);
    }

    $locationString = $request->latitude . ',' . $request->longitude;
    $currentTime = now('Asia/Jakarta')->format('H:i:s');

    // 3. ABSEN MASUK (Jika record belum ada ATAU record ada tapi belum check_in)
    if (!$existing || is_null($existing->check_in)) {
        if (!$existing) {
            $attendance = Attendance::create([
                'student_id' => $student->id,
                'date'       => $today,
                'status'     => 'hadir',
                'location'   => $locationString,
                'check_in'   => $currentTime,
                'method'     => 'gps',
                'entry_type' => 'system',
            ]);
        } else {
            $existing->update([
                'status'     => 'hadir',
                'location'   => $locationString,
                'check_in'   => $currentTime,
                'method'     => 'gps',
            ]);
            $attendance = $existing->fresh(); // Ambil data terbaru dari DB
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Absen masuk berhasil! Jam ' . $attendance->check_in,
            'data'    => array_merge($attendance->toArray(), [
                'latitude'   => (float) $request->latitude,
                'longitude'  => (float) $request->longitude,
                'distance_m' => round($distance),
            ])
        ], 201);
    }

    // 4. ABSEN PULANG (Kondisi: $existing ada DAN $existing->check_in tidak null)
    $existing->update([
        'check_out' => $currentTime,
        'location'  => $locationString,
    ]);

    $updatedAttendance = $existing->fresh(); // Gunakan fresh() agar instance ter-update

    return response()->json([
        'status'  => 'success',
        'message' => 'Absen pulang berhasil! Jam ' . $updatedAttendance->check_out,
        'data'    => array_merge($updatedAttendance->toArray(), [
            'latitude'   => (float) $request->latitude,
            'longitude'  => (float) $request->longitude,
            'distance_m' => round($distance),
        ])
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