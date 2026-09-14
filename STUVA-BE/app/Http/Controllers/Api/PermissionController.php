<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermissionRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PermissionController extends Controller
{
    // 1. Siswa: Mengajukan Izin (Khusus Siswa)
    public function store(Request $request)
    {
        // Pengecekan Role: Hanya Siswa yang Boleh Membuat Pengajuan Izin
        if ($request->user()->role !== 'siswa') {
            return response()->json([
                'message' => 'Akses ditolak. Pengajuan izin hanya dapat dilakukan oleh Siswa.'
            ], 403);
        }

        $request->validate([
            'type' => 'required|in:sakit,izin,dispen',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $permission = PermissionRequest::create([
            'student_id' => $request->user()->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'status' => 'pending_parent', // Status awal: Menunggu Persetujuan Ortu
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil dibuat',
            'data' => $permission
        ], 201);
    }

    // 2. Ortu: Approve / Reject Izin Anak (Khusus Ortu)
    public function parentApproval(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'ortu') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Orang Tua yang dapat menyetujui tahap ini.'
            ], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|string|nullable',
        ]);

        $permission = PermissionRequest::findOrFail($id);

        if ($user->student_id !== $permission->student_id) {
            return response()->json([
                'message' => 'Akses ditolak. Anda bukan Orang Tua dari siswa ini.'
            ], 403);
        }

        $isApprove = $request->action === 'approve';

        $permission->update([
            'status' => $isApprove ? 'pending_teacher' : 'rejected_parent',
            'rejection_reason' => $isApprove ? null : $request->rejection_reason,
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Status pengajuan izin berhasil diperbarui oleh Orang Tua',
            'data' => $permission
        ]);
    }

    // 3. Guru: Approve / Reject Final (Khusus Guru)
    public function teacherApproval(Request $request, $id)
    {
        if ($request->user()->role !== 'guru') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Guru yang dapat melakukan verifikasi akhir.'
            ], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string',
        ]);

        $permission = PermissionRequest::findOrFail($id);

        $isApprove = $request->action === 'approve';

        $permission->update([
            'status' => $isApprove ? 'approved' : 'rejected_teacher',
            'rejection_reason' => $isApprove ? null : $request->rejection_reason,
            'processed_at' => now(),
        ]);

        // Jika disetujui Guru, otomatis masukkan/generate ke tabel presensi (Attendances)
        if ($isApprove) {
            $startDate = Carbon::parse($permission->start_date);
            $endDate   = Carbon::parse($permission->end_date);

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                if (!$date->isWeekend()) {
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $permission->student_id,
                            'date'       => $date->toDateString(),
                        ],
                        [
                            'permission_request_id' => $permission->id,
                            'status'                => $permission->type,
                        ]
                    );
                }
            }
        }

        return response()->json([
            'message' => 'Status pengajuan izin berhasil diverifikasi Guru',
            'data' => $permission
        ]);
    }

    // 4. Get List Izin (Sesuai Role)
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'siswa') {
            $data = PermissionRequest::where('student_id', $user->id)->latest()->get();
        } elseif ($user->role === 'ortu') {
            $data = PermissionRequest::where('student_id', $user->student_id)->latest()->get();
        } else { // Guru & Admin
            $data = PermissionRequest::with('student:id,name,class_name')->latest()->get();
        }

        return response()->json(['data' => $data]);
    }
}