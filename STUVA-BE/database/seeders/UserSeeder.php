<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Account Guru
        $guru = User::create([
            'name' => 'Pak Guru Test',
            'username' => '198501012026011001', // NIP
            'email' => 'guru@school.id',
            'password' => Hash::make('password123'),
            'role' => 'guru',
        ]);

        // 2. Buat Account Siswa
        $siswa = User::create([
            'name' => 'Siswa Test',
            'username' => '1234567890', // NISN
            'email' => 'siswa@school.id',
            'password' => Hash::make('password123'),
            'role' => 'siswa',
            'class_name' => 'XII RPL 1',
        ]);

        // 3. Buat Account Ortu (Berelasi langsung ke Siswa di atas)
        $ortu = User::create([
            'name' => 'Bapak/Ibu Ortu',
            'username' => '081234567890', // No HP / Username Ortu
            'email' => 'ortu@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'ortu',
            'student_id' => $siswa->id, // Mengikat Ortu ke Siswa
        ]);
    }
}