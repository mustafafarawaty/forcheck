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
        Schema::table('students', function (Blueprint $table): void {
            $table->decimal('balance', 12, 2)->default(0)->after('avatar_path');
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->decimal('balance', 12, 2)->default(0)->after('is_accepting_instant_sessions');
        });

        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->string('payment_status')->default('unpaid')->after('cancellation_reason');
            $table->timestamp('wallet_held_at')->nullable()->after('payment_status');
            $table->timestamp('settled_at')->nullable()->after('wallet_held_at');
            $table->timestamp('disputed_at')->nullable()->after('settled_at');
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_session_id')->nullable()->constrained('teacher_sessions')->nullOnDelete();
            $table->string('type');
            $table->string('direction');
            $table->string('status')->default('completed');
            $table->decimal('amount', 12, 2);
            $table->string('proof_path')->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index(['teacher_id', 'created_at']);
            $table->index(['teacher_session_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');

        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_status',
                'wallet_held_at',
                'settled_at',
                'disputed_at',
            ]);
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn('balance');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('balance');
        });
    }
};
