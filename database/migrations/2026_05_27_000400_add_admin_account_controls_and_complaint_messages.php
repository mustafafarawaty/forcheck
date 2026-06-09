<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('approval_status', 20)->default('approved')->after('is_accepting_instant_sessions');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->timestamp('disabled_at')->nullable()->after('approved_at');
            $table->text('disabled_reason')->nullable()->after('disabled_at');
            $table->softDeletes();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->timestamp('disabled_at')->nullable()->after('avatar_path');
            $table->text('disabled_reason')->nullable()->after('disabled_at');
            $table->softDeletes();
        });

        Schema::create('teacher_complaint_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_complaint_id')->constrained('teacher_complaints')->cascadeOnDelete();
            $table->string('sender_role', 20);
            $table->string('sender_name');
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_complaint_messages');

        Schema::table('students', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['disabled_at', 'disabled_reason']);
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['approval_status', 'approved_at', 'disabled_at', 'disabled_reason']);
        });
    }
};
