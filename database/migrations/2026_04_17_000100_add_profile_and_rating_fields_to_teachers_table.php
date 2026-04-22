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
        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('certificate_path');
            $table->decimal('rating_average', 3, 2)->default(0)->after('avatar_path');
            $table->unsignedInteger('ratings_count')->default(0)->after('rating_average');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn([
                'avatar_path',
                'rating_average',
                'ratings_count',
            ]);
        });
    }
};
