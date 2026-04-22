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
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('study_level');
            $table->text('about')->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_live_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_subject_id')->constrained('teacher_subjects')->cascadeOnDelete();
            $table->foreignId('teacher_session_id')->nullable()->constrained('teacher_sessions')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->boolean('is_accepting_instant_sessions')->default(false)->after('about');
        });

        Schema::table('teacher_availabilities', function (Blueprint $table): void {
            $table->boolean('is_booked')->default(false)->after('notes');
        });

        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable()->after('teacher_subject_id')->constrained()->nullOnDelete();
            $table->string('booking_type')->default('scheduled')->after('status');
            $table->timestamp('teacher_confirmed_at')->nullable()->after('booking_type');
            $table->timestamp('student_confirmed_at')->nullable()->after('teacher_confirmed_at');
            $table->timestamp('confirmation_deadline_at')->nullable()->after('student_confirmed_at');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('confirmation_deadline_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn([
                'booking_type',
                'teacher_confirmed_at',
                'student_confirmed_at',
                'confirmation_deadline_at',
                'last_reminder_sent_at',
            ]);
        });

        Schema::table('teacher_availabilities', function (Blueprint $table): void {
            $table->dropColumn('is_booked');
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn('is_accepting_instant_sessions');
        });

        Schema::dropIfExists('teacher_live_requests');
        Schema::dropIfExists('students');
    }
};
