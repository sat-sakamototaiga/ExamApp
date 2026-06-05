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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('difficulty', 20)->default('normal')->after('question_text');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('total_points')->default(0)->after('role');
            $table->timestamp('points_reset_at')->nullable()->after('total_points');
        });

        Schema::table('exam_results', function (Blueprint $table) {
            $table->integer('points_earned')->default(0)->after('question_count');
            $table->integer('bonus_points')->default(0)->after('points_earned');
        });

        Schema::create('point_reset_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('reset_interval_days')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_reset_settings');

        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'bonus_points']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_points', 'points_reset_at']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });
    }
};
