<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            
            // Relasi ke PermissionRequest (nullable karena kalau 'hadir' / 'alpha' tidak ada izin)
            $table->foreignId('permission_request_id')
                  ->nullable()
                  ->constrained('permission_requests')
                  ->nullOnDelete();

            $table->date('date');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'dispen', 'alpha'])->default('hadir');
            $table->text('notes')->nullable(); // Opsional untuk catatan manual dari Guru
            $table->timestamps();

            // 1 siswa hanya punya 1 record presensi per hari
            $table->unique(['student_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};