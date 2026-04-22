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
        Schema::create('teacher_session_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_session_id')->constrained()->cascadeOnDelete();
            $table->string('sender_role', 20);
            $table->string('sender_name')->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_session_messages');
    }
};
