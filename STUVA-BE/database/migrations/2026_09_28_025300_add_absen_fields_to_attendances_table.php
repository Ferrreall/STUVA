<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // ⚠️ sesuaikan nama tabel 'attendances' dengan tabel absensi kamu
            $table->time('check_in')->nullable()->after('status');
            $table->time('check_out')->nullable()->after('check_in');
            $table->string('method')->nullable()->after('check_out'); // 'gps' | 'qr' | 'admin'
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in', 'check_out', 'method']);
        });
    }
};
