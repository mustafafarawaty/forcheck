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
        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('education_stage')->default('secondary')->after('specialization');
            $table->string('city')->nullable()->change();
        });

        Schema::table('teacher_subjects', function (Blueprint $table): void {
            $table->unsignedInteger('hourly_rate_syp')->default(0)->after('level');
        });

        DB::table('teachers')
            ->select(['id', 'education_levels'])
            ->orderBy('id')
            ->lazy()
            ->each(function (object $teacher): void {
                $levels = json_decode($teacher->education_levels ?? '[]', true);
                $stage = in_array('university', $levels ?? [], true) ? 'university' : 'secondary';

                DB::table('teachers')
                    ->where('id', $teacher->id)
                    ->update([
                        'education_stage' => $stage,
                        'education_levels' => json_encode(
                            $stage === 'university'
                                ? ['primary', 'middle', 'secondary', 'university']
                                : ['primary', 'middle', 'secondary']
                        ),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table): void {
            $table->dropColumn('hourly_rate_syp');
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn('education_stage');
        });
    }
};
