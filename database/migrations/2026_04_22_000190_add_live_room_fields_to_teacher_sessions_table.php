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
            $table->timestamp('started_at')->nullable()->after('scheduled_at');
            $table->timestamp('teacher_joined_at')->nullable()->after('student_confirmed_at');
            $table->timestamp('student_joined_at')->nullable()->after('teacher_joined_at');
            $table->timestamp('join_deadline_at')->nullable()->after('student_joined_at');
            $table->text('teacher_private_notes')->nullable()->after('notes');
            $table->text('student_summary_notes')->nullable()->after('teacher_private_notes');
            $table->timestamp('recording_expires_at')->nullable()->after('recording_url');
            $table->string('ended_by_role')->nullable()->after('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'started_at',
                'teacher_joined_at',
                'student_joined_at',
                'join_deadline_at',
                'teacher_private_notes',
                'student_summary_notes',
                'recording_expires_at',
                'ended_by_role',
            ]);
        });
    }
};
