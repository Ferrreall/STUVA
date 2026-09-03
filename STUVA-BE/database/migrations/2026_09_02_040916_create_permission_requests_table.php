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
    Schema::create('permission_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
        $table->enum('type', ['sakit', 'izin', 'dispen']);
        $table->date('start_date');
        $table->date('end_date');
        $table->text('reason');
        $table->string('attachment')->nullable(); // Lokasi path foto surat
        $table->enum('status', [
            'pending_parent', 
            'pending_teacher', 
            'approved', 
            'rejected_parent', 
            'rejected_teacher'
        ])->default('pending_parent');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_requests');
    }
};
