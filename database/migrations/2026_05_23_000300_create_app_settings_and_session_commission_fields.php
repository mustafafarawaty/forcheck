<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            'key' => 'admin_commission_percentage',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->decimal('admin_commission_percentage', 5, 2)->default(0)->after('price');
            $table->decimal('admin_commission_amount', 12, 2)->default(0)->after('admin_commission_percentage');
            $table->decimal('teacher_earning_amount', 12, 2)->default(0)->after('admin_commission_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'admin_commission_percentage',
                'admin_commission_amount',
                'teacher_earning_amount',
            ]);
        });

        Schema::dropIfExists('app_settings');
    }
};
