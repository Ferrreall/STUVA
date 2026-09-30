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
    // 1. Ambil List User (Bisa Filter Role + Pagination 10)
    public function index(Request $request)
    {
        try {
            // Eager loading relasi student
            $query = User::with('student:id,name,class_name');
    
            // Filter berdasarkan role jika dikirim dari FE (misal: ?role=siswa, ?role=guru, ?role=ortu)
            if ($request->has('role') && !empty($request->role)) {
                $query->where('role', $request->role);
            }
    
            // Ambil data dengan pagination 10 item per halaman
            // per_page bisa diset dinamik kalau FE minta, default 10
            $perPage = $request->get('per_page', 10);
            $users = $query->latest()->paginate($perPage);
    
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
    
    // 2. Endpoint Khusus Card Dashboard (Total Siswa, Guru, Ortu)
    public function getStats()
    {
        try {
            $totalSiswa = User::where('role', 'siswa')->count();
            $totalGuru  = User::where('role', 'guru')->count();
            $totalOrtu  = User::where('role', 'ortu')->count();
    
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'total_siswa' => $totalSiswa,
                    'total_guru'  => $totalGuru,
                    'total_ortu'  => $totalOrtu,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil statistik user: ' . $e->getMessage()
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
            'class_name'   => 'required_if:role,siswa|nullable',
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

            $classNameValue = null;
if ($request->role === 'siswa') {
    $classNameValue = $validated['class_name'] ?? null;
} elseif ($request->role === 'guru') {
    // Jika dikirim array dari FE: ["XII RPL 1", "XII RPL 2"], langsung simpan array-nya!
    // Laravel $casts 'array' akan otomatis mengubahnya ke JSON di DB.
    $classNameValue = $request->class_name;
}

            $user = User::create([
                'name'         => $validated['name'],
                'username'     => $validated['username'],
                'email'        => $validated['email'],
                'password'     => Hash::make($validated['password']),
                'role'         => $validated['role'],
                'class_name'   => $classNameValue,
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
            'class_name'   => 'required_if:role,siswa|nullable',
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

        $classNameValue = null;
if ($request->role === 'siswa') {
    $classNameValue = $validated['class_name'] ?? null;
} elseif ($request->role === 'guru') {
    // Jika dikirim array dari FE: ["XII RPL 1", "XII RPL 2"], langsung simpan array-nya!
    // Laravel $casts 'array' akan otomatis mengubahnya ke JSON di DB.
    $classNameValue = $request->class_name;
}

            $dataToUpdate = [
                'name'         => $validated['name'],
                'username'     => $validated['username'],
                'email'        => $validated['email'],
                'role'         => $validated['role'],
                'class_name'   => $classNameValue,
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

public function getAvailableClasses()
{
    // 1. Daftar kelas standar sekolah (Default Options)
    $defaultClasses = [
        'X RPL 1',
        'X RPL 2',
        'XI RPL 1',
        'XI RPL 2',
        'XII RPL 1',
        'XII RPL 2',
    ];

    $options = [];
    $registeredClasses = []; // Untuk melacak kelas yang sudah diampu guru

    // 2. Ambil data guru yang sudah ada di database
    $teachers = User::where('role', 'guru')
        ->whereNotNull('class_name')
        ->where('class_name', '!=', '')
        ->get(['id', 'name', 'class_name']);

    foreach ($teachers as $teacher) {
        $classes = $teacher->class_name;

        if (is_string($classes)) {
            $decoded = json_decode($classes, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $classes = $decoded;
            }
        }

        if (is_array($classes)) {
            foreach ($classes as $class) {
                if (!empty($class)) {
                    $options[] = [
                        'class_name'   => $class,
                        'label'        => $class . ' -- ' . $teacher->name,
                        'teacher_name' => $teacher->name
                    ];
                    $registeredClasses[] = $class;
                }
            }
        } elseif (is_string($classes) && !empty($classes)) {
            $options[] = [
                'class_name'   => $classes,
                'label'        => $classes . ' -- ' . $teacher->name,
                'teacher_name' => $teacher->name
            ];
            $registeredClasses[] = $classes;
        }
    }

    // 3. Tambahkan kelas standar jika belum terdaftar dari guru mana pun
    foreach ($defaultClasses as $defaultClass) {
        if (!in_array($defaultClass, $registeredClasses)) {
            $options[] = [
                'class_name'   => $defaultClass,
                'label'        => $defaultClass, // Hanya nama kelas biasa
                'teacher_name' => null
            ];
        }
    }

    return response()->json([
        'status' => 'success',
        'data'   => $options
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
