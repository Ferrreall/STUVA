<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    /**
     * READ: Menampilkan semua data presensi siswa (dengan filter tanggal & pencarian opsional)
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['permissionRequest'])
            ->join('users', 'attendances.student_id', '=', 'users.id')
            ->select('attendances.*', 'users.name as student_name', 'users.email as student_email');

        // Filter berdasarkan tanggal jika dikirim dari frontend (default hari ini)
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('attendances.date', $request->date);
        }

        // Filter pencarian nama siswa
        if ($request->has('search') && $request->search != '') {
            $query->where('users.name', 'like', '%' . $request->search . '%');
        }

        $attendances = $query->orderBy('attendances.date', 'desc')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $attendances,
        ]);
    }

    /**
     * CREATE: Menambahkan catatan presensi siswa secara manual oleh Admin/Guru
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'date'       => 'required|date',
            'status'     => 'required|in:hadir,sakit,izin,dispen,alpha',
            'notes'      => 'nullable|string|max:255',
        ]);

        // Cek apakah siswa sudah punya catatan presensi di tanggal tersebut
        $exists = Attendance::where('student_id', $request->student_id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi siswa pada tanggal tersebut sudah ada.',
            ], 422);
        }

        $attendance = Attendance::create([
            'student_id' => $request->student_id,
            'date'       => $request->date,
            'status'     => $request->status,
            'notes'      => $request->notes,
            'entry_type' => 'admin',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data presensi berhasil ditambahkan.',
            'data'    => $attendance,
        ], 201);
    }

    /**
     * SHOW: Menampilkan detail 1 record presensi
     */
    public function show($id)
    {
        $attendance = Attendance::with(['permissionRequest'])
            ->join('users', 'attendances.student_id', '=', 'users.id')
            ->select('attendances.*', 'users.name as student_name', 'users.email as student_email')
            ->where('attendances.id', $id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $attendance,
        ]);
    }

    /**
     * UPDATE: Memperbarui status presensi atau menambah/edit notes manual
     */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:hadir,sakit,izin,dispen,alpha',
            'notes'  => 'nullable|string|max:255',
        ]);

        $attendance->update([
            'status' => $request->status,
            'notes'  => $request->notes,
            'entry_type' => 'admin', // Menandai bahwa update dilakukan oleh Admin
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data presensi berhasil diperbarui.',
            'data'    => $attendance,
        ]);
    }

    /**
     * DELETE: Menghapus data presensi
     */
    public function destroy($id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan.',
            ], 404);
        }

        $attendance->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data presensi berhasil dihapus.',
        ]);
    }

    public function __construct()
    {
        // Cek jika user yang login bukan admin (atau guru jika guru tidak boleh akses CRUD admin)
        if (auth('sanctum')->check() && auth('sanctum')->user()->role !== 'admin') {
            abort(response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Endpoint ini khusus Admin.'
            ], 403));
        }
    }
}