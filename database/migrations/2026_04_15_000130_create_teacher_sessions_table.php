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
        Schema::create('teacher_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_name');
            $table->dateTime('scheduled_at');
            $table->dateTime('ended_at')->nullable();
            $table->string('status')->default('upcoming');
            $table->decimal('price', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('chat_excerpt')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_sessions');
    }
};
