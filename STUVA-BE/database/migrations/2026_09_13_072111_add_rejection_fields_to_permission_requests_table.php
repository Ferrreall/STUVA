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
    Schema::table('permission_requests', function (Blueprint $table) { 
        $table->text('rejection_reason')->nullable()->after('status');
        $table->timestamp('processed_at')->nullable()->after('rejection_reason');
    });
}

public function down(): void
{
    Schema::table('permission_requests', function (Blueprint $table) {
        $table->dropColumn(['rejection_reason', 'processed_at']);
    });
}
};
