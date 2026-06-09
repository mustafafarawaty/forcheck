<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->text('admin_note')->nullable()->after('description');
            $table->timestamp('reviewed_at')->nullable()->after('admin_note');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->dropColumn(['admin_note', 'reviewed_at']);
        });
    }
};
