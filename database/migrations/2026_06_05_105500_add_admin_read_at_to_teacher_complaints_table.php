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
        if (Schema::hasTable('teacher_complaints')) {
            Schema::table('teacher_complaints', function (Blueprint $table): void {
                if (! Schema::hasColumn('teacher_complaints', 'admin_read_at')) {
                    $table->timestamp('admin_read_at')->nullable()->after('student_read_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teacher_complaints')) {
            Schema::table('teacher_complaints', function (Blueprint $table): void {
                if (Schema::hasColumn('teacher_complaints', 'admin_read_at')) {
                    $table->dropColumn('admin_read_at');
                }
            });
        }
    }
};
