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
        if (Schema::hasTable('teacher_sessions')) {
            Schema::table('teacher_sessions', function (Blueprint $table): void {
                if (! Schema::hasColumn('teacher_sessions', 'teacher_read_at')) {
                    $table->timestamp('teacher_read_at')->nullable()->after('student_joined_at');
                }

                if (! Schema::hasColumn('teacher_sessions', 'admin_read_at')) {
                    $table->timestamp('admin_read_at')->nullable()->after('teacher_read_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teacher_sessions')) {
            Schema::table('teacher_sessions', function (Blueprint $table): void {
                if (Schema::hasColumn('teacher_sessions', 'admin_read_at')) {
                    $table->dropColumn('admin_read_at');
                }

                if (Schema::hasColumn('teacher_sessions', 'teacher_read_at')) {
                    $table->dropColumn('teacher_read_at');
                }
            });
        }
    }
};
