<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_import_questions_from_legacy_csv_format(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
        ]);
        $exam = Exam::create(['name' => 'CSV取込テスト']);

        $csv = implode("\n", [
            '問題文,全体解説,選択肢1,正解1,解説1,選択肢2,正解2,解説2,選択肢3,正解3,解説3,選択肢4,正解4,解説4',
            '日本の首都はどこですか？,首都は東京です,東京,1,正解です,大阪,0,違います,名古屋,0,違います,福岡,0,違います',
        ]);

        $csvFile = UploadedFile::fake()->createWithContent('questions.csv', "\xEF\xBB\xBF{$csv}");

        $response = $this->actingAs($teacher)->post(route('questions.import'), [
            'exam_id' => $exam->id,
            'csv_file' => $csvFile,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $question = Question::query()->first();
        $this->assertNotNull($question);
        $this->assertSame('日本の首都はどこですか？', $question->question_text);
        $this->assertNull($question->question_image_path);
        $this->assertCount(4, $question->options);
    }

    public function test_teacher_can_import_questions_with_images_using_zip(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
        ]);
        $exam = Exam::create(['name' => 'CSV画像取込テスト']);

        $csv = implode("\n", [
            '問題文,問題画像,全体解説,選択肢1,選択肢1画像,正解1,解説1,選択肢2,選択肢2画像,正解2,解説2,選択肢3,選択肢3画像,正解3,解説3,選択肢4,選択肢4画像,正解4,解説4',
            '日本の首都はどこですか？,q1.png,首都は東京です,東京,o1.png,1,正解です,大阪,,0,違います,名古屋,,0,違います,福岡,,0,違います',
        ]);

        $csvFile = UploadedFile::fake()->createWithContent('questions_with_images.csv', "\xEF\xBB\xBF{$csv}");

        $tmpDir = storage_path('app/testing-import-images-' . uniqid());
        mkdir($tmpDir, 0777, true);

        try {
            $questionImagePath = $tmpDir . '/q1.png';
            $optionImagePath = $tmpDir . '/o1.png';
            file_put_contents($questionImagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO9W9f8AAAAASUVORK5CYII='));
            file_put_contents($optionImagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO9W9f8AAAAASUVORK5CYII='));

            $zipPath = $tmpDir . '/images.zip';
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
            $zip->addFile($questionImagePath, 'q1.png');
            $zip->addFile($optionImagePath, 'o1.png');
            $zip->close();

            $zipUpload = new UploadedFile($zipPath, 'images.zip', 'application/zip', null, true);

            $response = $this->actingAs($teacher)->post(route('questions.import'), [
                'exam_id' => $exam->id,
                'csv_file' => $csvFile,
                'images_zip' => $zipUpload,
            ]);
        } finally {
            @unlink($tmpDir . '/q1.png');
            @unlink($tmpDir . '/o1.png');
            @unlink($tmpDir . '/images.zip');
            @rmdir($tmpDir);
        }

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $question = Question::query()->with('options')->first();
        $this->assertNotNull($question);
        $this->assertNotNull($question->question_image_path);
        Storage::disk('public')->assertExists($question->question_image_path);

        $firstOption = $question->options()->orderBy('id')->first();
        $this->assertNotNull($firstOption);
        $this->assertNotNull($firstOption->option_image_path);
        Storage::disk('public')->assertExists($firstOption->option_image_path);
    }
}
