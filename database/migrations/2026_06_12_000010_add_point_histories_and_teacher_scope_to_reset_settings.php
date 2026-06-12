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
        Schema::table('point_reset_settings', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->unique('teacher_id');
        });

        Schema::create('point_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('event_type', 40);
            $table->integer('points_delta');
            $table->integer('balance_after');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['teacher_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_histories');

        Schema::table('point_reset_settings', function (Blueprint $table) {
            $table->dropUnique(['teacher_id']);
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};