<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermissionRequest;
use App\Models\LocationLog;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    // 1. Get status live & presensi anak
    public function getChildStatus(Request $request)
    {
        try {
            $parent = $request->user();
            $studentId = $parent->student_id;

            if (!$studentId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akun Orang Tua ini belum dihubungkan ke siswa manapun.'
                ], 400);
            }

            // Ambil data lokasi & baterai terakhir anak
            $lastLocation = LocationLog::where('student_id', $studentId)
                ->latest('recorded_at')
                ->first();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'student'       => $parent->student, // Memanggil relasi student dari model User
                    'last_location' => $lastLocation ? [
                        'latitude'     => $lastLocation->latitude,
                        'longitude'    => $lastLocation->longitude,
                        'battery_level'=> $lastLocation->battery_level,
                        'updated_at'   => $lastLocation->recorded_at,
                    ] : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data anak: ' . $e->getMessage()
            ], 500);
        }
    }

    // 2. Get daftar izin anak yang butuh approval ortu
    public function getPendingPermissions(Request $request)
    {
        try {
            $studentId = $request->user()->student_id;

            $permissions = PermissionRequest::where('student_id', $studentId)
                ->where('status', 'pending_parent')
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil daftar izin: ' . $e->getMessage()
            ], 500);
        }
    }
}