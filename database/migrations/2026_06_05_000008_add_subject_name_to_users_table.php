<?php

use App\Models\User;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('subject_name')->nullable()->after('role');
        });

        $subjects = ['数学', '英語', '理科', '社会', '国語'];

        DB::table('users')
            ->where('role', User::ROLE_TEACHER)
            ->orderBy('id')
            ->select('id')
            ->get()
            ->each(function ($teacher) use ($subjects): void {
                DB::table('users')
                    ->where('id', $teacher->id)
                    ->update([
                        'subject_name' => $subjects[array_rand($subjects)],
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('subject_name');
        });
    }
};
