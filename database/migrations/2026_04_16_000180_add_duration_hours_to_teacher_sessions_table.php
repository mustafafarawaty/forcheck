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
        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('duration_hours')->default(1)->after('last_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->dropColumn('duration_hours');
        });
    }
};
