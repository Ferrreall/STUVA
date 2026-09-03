<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationLog;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    // 1. Endpoint untuk Siswa (PWA mengirim koordinat & baterai)
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'battery_level' => 'nullable|integer|between:0,100',
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
        // 1. Jika Siswa mencoba akses, langsung tolak (Siswa hanya mengirim koordinat via /location/ping)
        if ($user->role === 'siswa') {
            return response()->json([
                'message' => 'Akses ditolak. Fitur pemantauan lokasi hanya untuk Guru dan Orang Tua.'
            ], 403);
        }
        // 2. Jika yang akses Ortu, hanya tampilkan lokasi anaknya sendiri
        if ($user->role === 'ortu') {
            if (!$user->student_id) {
                return response()->json(['message' => 'Data anak tidak ditemukan'], 404);
            }

            $latestLocation = LocationLog::where('student_id', $user->student_id)
                ->latest('recorded_at')
                ->first();

            return response()->json(['data' => $latestLocation]);
        }

        // 3. Jika yang akses Guru, tampilkan lokasi TERAKHIR dari SEMUA siswa
        if ($user->role === 'guru') {
            $latestLogs = LocationLog::with('student:id,name,class_name')
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('location_logs')
                        ->groupBy('student_id');
                })
                ->get();

            return response()->json(['data' => $latestLogs]);
        }

        return response()->json(['message' => 'Akses ditolak.'], 403);
    }
}
