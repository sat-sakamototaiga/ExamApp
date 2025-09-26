<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // questionsテーブルにexam_idを追加
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('cascade')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // ロールバック時にexam_idを削除
            $table->dropForeign(['exam_id']);
            $table->dropColumn('exam_id');
        });
    }
};