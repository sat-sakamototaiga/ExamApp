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
            $table->string('question_image_path')->nullable()->after('question_text');
        });

        Schema::table('options', function (Blueprint $table) {
            $table->string('option_image_path')->nullable()->after('option_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('option_image_path');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('question_image_path');
        });
    }
};
