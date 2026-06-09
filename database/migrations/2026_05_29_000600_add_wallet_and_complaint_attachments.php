<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->string('admin_attachment_path')->nullable()->after('proof_path');
        });

        Schema::table('teacher_complaints', function (Blueprint $table): void {
            $table->dropForeign(['teacher_id']);
            $table->foreignId('teacher_id')->nullable()->change();
            $table->foreign('teacher_id')->references('id')->on('teachers')->nullOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->after('teacher_session_id')->constrained('wallet_transactions')->nullOnDelete();
            $table->string('attachment_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_complaints', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('wallet_transaction_id');
            $table->dropColumn('attachment_path');
            $table->dropForeign(['teacher_id']);
            $table->foreignId('teacher_id')->nullable(false)->change();
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
        });

        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->dropColumn('admin_attachment_path');
        });
    }
};
