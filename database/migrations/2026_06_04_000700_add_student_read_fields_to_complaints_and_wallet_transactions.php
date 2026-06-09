<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_complaints', function (Blueprint $table): void {
            $table->timestamp('student_read_at')->nullable()->after('submitted_at');
        });

        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->timestamp('student_read_at')->nullable()->after('reviewed_at');
        });

        DB::table('teacher_complaints')->update(['student_read_at' => now()]);
        DB::table('wallet_transactions')->whereNotNull('student_id')->update(['student_read_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->dropColumn('student_read_at');
        });

        Schema::table('teacher_complaints', function (Blueprint $table): void {
            $table->dropColumn('student_read_at');
        });
    }
};
