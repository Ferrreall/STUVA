<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationLog;
use App\Models\PermissionRequest;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    // 1. Endpoint untuk Siswa (PWA mengirim koordinat & baterai)
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'battery_level' => 'nullable|integer|min:0|max:100',
        ]);

        $log = LocationLog::create([
            'student_id' => $request->user()->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'battery_level' => $request->battery_level,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Lokasi berhasil diperbarui',
            'data' => $log
        ], 201);
    }

    // 2. Endpoint untuk Guru & Ortu (Mengambil lokasi siswa terkini)
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Tolak Siswa
        if ($user->role === 'siswa') {
            return response()->json([
                'message' => 'Akses ditolak. Fitur pemantauan lokasi hanya untuk Guru, Admin, dan Orang Tua.'
            ], 403);
        }

        // 2. Akses Orang Tua (Lokasi anaknya saja)
        if ($user->role === 'ortu') {
            if (!$user->student_id) {
                return response()->json(['message' => 'Data anak tidak ditemukan'], 404);
            }

            $latestLocation = LocationLog::where('student_id', $user->student_id)
                ->latest('recorded_at')
                ->first();

            return response()->json([
                'status' => 'success',
                'data'   => $latestLocation
            ]);
        }

        // 3. Akses Guru & Admin
        if (in_array($user->role, ['guru', 'admin'])) {
            // Ambil ID siswa yang SEDANG izin keluar (approved & belum kembali)
            $activeStudentIds = PermissionRequest::where('status', 'approved')
                ->pluck('student_id');

            // Base Query: Ambil lokasi terbaru dari siswa yang sedang izin
            $query = LocationLog::with('student:id,name,class_name')
                ->whereIn('student_id', $activeStudentIds)
                ->whereIn('id', function ($sub) {
                    $sub->selectRaw('MAX(id)')
                        ->from('location_logs')
                        ->groupBy('student_id');
                });

            // Filter opsional berdasarkan kelas (jika dikirim dari FE: /api/location/live?class_name=XII RPL 1)
            if ($request->has('class_name') && $request->class_name !== '') {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_name', $request->class_name);
                });
            }

            // Ambil data dengan pagination (default 20 item per page)
            $latestLogs = $query->paginate(20);

            return response()->json([
                'status' => 'success',
                'data'   => $latestLogs
            ]);
        }

        return response()->json(['message' => 'Akses ditolak.'], 403);
    }
}
