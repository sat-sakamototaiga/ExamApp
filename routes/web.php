<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\FlagController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () { return 'Test page is working!'; });

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 問題管理
    Route::resource('questions', QuestionController::class);
    Route::get('/questions/import', [QuestionController::class, 'importForm'])->name('questions.import.form');
    Route::post('/questions/import', [QuestionController::class, 'import'])->name('questions.import');

    // 試験管理
    Route::resource('exams', ExamController::class);

    // 試験出題
    Route::get('/quiz', [QuizController::class, 'selectExam'])->name('quiz.select_exam');
    Route::get('/quiz/{exam}', [QuizController::class, 'index'])->name('quiz.index');
    Route::post('/quiz/{exam}/answer', [QuizController::class, 'answer'])->name('quiz.answer');

    // フラグ管理
    Route::post('/questions/{question}/toggle-flag', [FlagController::class, 'toggle'])
    ->middleware('auth')
    ->name('questions.toggle_flag');
});

require __DIR__.'/auth.php';
