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
        Schema::create('questions', function (Blueprint $table) {
            $table->id(); // idカラム (BIGINT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
            $table->text('question_text'); // 問題文
            $table->text('overall_explanation')->nullable(); // 新しい解説カラム
            $table->timestamps(); // created_at と updated_at カラム
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
