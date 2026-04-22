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
        Schema::table('teacher_complaints', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable()->after('teacher_id')->constrained()->nullOnDelete();
            $table->string('submitted_by', 20)->default('teacher')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_complaints', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn('submitted_by');
        });
    }
};
