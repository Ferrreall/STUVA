<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\PermissionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    public function overview(Request $request)
    {
        $today = Carbon::today()->toDateString();

        // 1. Rekap Presensi Hari Ini
        $totalHadir = Attendance::whereDate('date', $today)
            ->where('status', 'hadir')
            ->count();

        $totalSakit = Attendance::whereDate('date', $today)
            ->where('status', 'sakit')
            ->count();

        $totalIzin = Attendance::whereDate('date', $today)
            ->where('status', 'izin')
            ->count();

        $totalDispen = Attendance::whereDate('date', $today)
            ->where('status', 'dispen')
            ->count();

        $totalAlpha = Attendance::whereDate('date', $today)
            ->where('status', 'alpha')
            ->count();

        $totalTidakHadir = $totalSakit + $totalIzin + $totalDispen + $totalAlpha;

        // 2. Monitoring Pending Permission Requests
        $pendingParent  = PermissionRequest::where('status', 'pending_parent')->count();
        $pendingTeacher = PermissionRequest::where('status', 'pending_teacher')->count();
        $totalPending   = $pendingParent + $pendingTeacher;

        // 3. Ringkasan Total User Aplikasi
        $totalStudents = User::where('role', 'siswa')->count();
        $totalParents  = User::where('role', 'ortu')->count();
        $totalTeachers = User::where('role', 'guru')->count();
        $totalAdmins   = User::where('role', 'admin')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'attendance_today' => [
                    'date'              => $today,
                    'total_hadir'       => $totalHadir,
                    'total_sakit'       => $totalSakit,
                    'total_izin'        => $totalIzin,
                    'total_dispen'      => $totalDispen,
                    'total_alpha'       => $totalAlpha,
                    'total_tidak_hadir' => $totalTidakHadir,
                ],
                'permissions' => [
                    'pending_parent'  => $pendingParent,
                    'pending_teacher' => $pendingTeacher,
                    'total_pending'   => $totalPending,
                ],
                'users' => [
                    'total_students'  => $totalStudents,
                    'total_parents'   => $totalParents,
                    'total_teachers'  => $totalTeachers,
                    'total_admins'    => $totalAdmins,
                ]
            ]
        ]);
    }
}