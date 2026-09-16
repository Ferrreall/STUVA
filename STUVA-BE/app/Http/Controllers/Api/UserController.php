<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambahkan user baru.'
            ], 403);
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'username'     => 'required|string|max:255|unique:users',
            'email'        => 'required|string|email|max:255|unique:users',
            'password'     => 'required|string|min:8',
            'role'         => 'required|in:siswa,ortu,guru',
            'class_name'   => 'required_if:role,siswa|nullable|string|max:255',
            'student_id'   => 'required_if:role,ortu|nullable|exists:users,id',
            // Field profil baru
            'nisn'         => 'required_if:role,siswa|nullable|string|max:20|unique:users,nisn',
            'nip'          => 'required_if:role,guru|nullable|string|max:30|unique:users,nip',
            'subject'      => 'required_if:role,guru|nullable|string|max:255',
            'gender'       => 'nullable|in:Laki-laki,Perempuan',
            'birth_place'  => 'nullable|string|max:255',
            'birth_date'   => 'nullable|date',
            'address'      => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
        ]);

        try {
            $user = User::create([
                'name'         => $validated['name'],
                'username'     => $validated['username'],
                'email'        => $validated['email'],
                'password'     => Hash::make($validated['password']),
                'role'         => $validated['role'],
                'class_name'   => $request->role === 'siswa' ? $validated['class_name'] : null,
                'student_id'   => $request->role === 'ortu' ? $validated['student_id'] : null,
                'nisn'         => $request->role === 'siswa' ? ($validated['nisn'] ?? null) : null,
                'nip'          => $request->role === 'guru' ? ($validated['nip'] ?? null) : null,
                'subject'      => $request->role === 'guru' ? ($validated['subject'] ?? null) : null,
                'gender'       => $validated['gender'] ?? null,
                'birth_place'  => $validated['birth_place'] ?? null,
                'birth_date'   => $validated['birth_date'] ?? null,
                'address'      => $validated['address'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
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

    // Detail 1 User
    public function show($id)
    {
        try {
            $user = User::with('student:id,name,class_name')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan'
            ], 440);
        }
    }

    // Update Data User
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'username'     => 'required|string|max:255|unique:users,username,' . $id,
            'email'        => 'required|string|email|max:255|unique:users,email,' . $id,
            'password'     => 'nullable|string|min:8',
            'role'         => 'required|in:siswa,ortu,guru,admin',
            'class_name'   => 'required_if:role,siswa|nullable|string|max:255',
            'student_id'   => 'required_if:role,ortu|nullable|exists:users,id',
            // Field profil baru
            'nisn'         => 'required_if:role,siswa|nullable|string|max:20|unique:users,nisn,' . $id,
            'gender'       => 'nullable|in:Laki-laki,Perempuan',
            'birth_place'  => 'nullable|string|max:255',
            'birth_date'   => 'nullable|date',
            'address'      => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'nip'          => 'required_if:role,guru|nullable|string|max:30|unique:users,nip,' . $id,
            'subject'      => 'required_if:role,guru|nullable|string|max:255',
        ]);

        try {
            $dataToUpdate = [
                'name'         => $validated['name'],
                'username'     => $validated['username'],
                'email'        => $validated['email'],
                'role'         => $validated['role'],
                'class_name'   => $request->role === 'siswa' ? $validated['class_name'] : null,
                'student_id'   => $request->role === 'ortu' ? $validated['student_id'] : null,
                'nisn'         => $request->role === 'siswa' ? ($validated['nisn'] ?? null) : null,
                'gender'       => $validated['gender'] ?? null,
                'birth_place'  => $validated['birth_place'] ?? null,
                'birth_date'   => $validated['birth_date'] ?? null,
                'address'      => $validated['address'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'nip'          => $request->role === 'guru' ? ($validated['nip'] ?? null) : null,
                'subject'      => $request->role === 'guru' ? ($validated['subject'] ?? null) : null,
            ];

            if ($request->filled('password')) {
                $dataToUpdate['password'] = Hash::make($validated['password']);
            }

            $user->update($dataToUpdate);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data user berhasil diperbarui',
                'data'    => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data user: ' . $e->getMessage()
            ], 500);
        }
    }

    // 1. Update Profil Sendiri
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'gender'       => 'nullable|in:Laki-laki,Perempuan',
            'birth_place'  => 'nullable|string|max:255',
            'birth_date'   => 'nullable|date',
            'address'      => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data'    => $user
        ]);
    }

    // 2. Ganti Password Sendiri
    public function changePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'], // butuh new_password_confirmation dari FE
        ]);

        // Cek apakah password lama sesuai
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password saat ini tidak sesuai'
            ], 422);
        }

        // Update ke password baru
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil diubah'
        ]);
    }

    // Hapus User
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'User berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus user: ' . $e->getMessage()
            ], 500);
        }
    }
}
