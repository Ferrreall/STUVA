<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Ambil list siswa aja (buat dropdown di FE pas bikin akun Ortu)
    public function getStudents()
    {
        try {
            $students = User::where('role', 'siswa')
                ->select('id', 'name', 'class_name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $students
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data siswa: ' . $e->getMessage()
            ], 500);
        }
    }

    // Ambil semua data user (bisa dengan filter role)
    public function index(Request $request)
    {
        try {
            // Eager loading relasi student biar tau ortu ini connect ke siswa siapa
            $query = User::with('student:id,name,class_name');

            // Filter berdasarkan role jika diberikan (misal: ?role=ortu)
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            $users = $query->latest()->get();

            return response()->json([
                'status' => 'success',
                'data'   => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data user: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tambah User Baru (Siswa / Ortu / Guru)
    public function store(Request $request)
    {
        // Validasi ditaruh DILUAR try-catch biar otomatis balikin 422 kalau input invalid
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:255|unique:users',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:siswa,ortu,guru',
            'class_name' => 'required_if:role,siswa|nullable|string|max:255',
            'student_id' => 'required_if:role,ortu|nullable|exists:users,id',
        ]);

        try {
            $user = User::create([
                'name'       => $validated['name'],
                'username'   => $validated['username'],
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
                'role'       => $validated['role'],
                'class_name' => $request->role === 'siswa' ? $validated['class_name'] : null,
                'student_id' => $request->role === 'ortu' ? $validated['student_id'] : null,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'User berhasil ditambahkan',
                'data'    => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menambahkan user: ' . $e->getMessage()
            ], 500);
        }
    }
}