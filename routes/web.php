<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\FlagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Teacher\StudentProgressController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/test', function () { return 'Test page is working!'; });

Route::middleware(['auth', 'quiz.prevent.random.navigation', 'quiz.reset.on.navigation'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('verified')
        ->name('dashboard');
    Route::get('/dashboard/feedback-history', [DashboardController::class, 'feedbackHistory'])
        ->middleware('verified')
        ->name('dashboard.feedback-history');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 問題・試験管理（教師以上）
    Route::middleware('role.hierarchy:teacher')->group(function () {
        Route::get('/questions/import', [QuestionController::class, 'importForm'])->name('questions.import.form');
        Route::get('/questions/import/template', [QuestionController::class, 'downloadTemplate'])->name('questions.import.template');
        Route::post('/questions/import', [QuestionController::class, 'import'])->name('questions.import');
        Route::resource('questions', QuestionController::class)->whereNumber('question');

        Route::resource('exams', ExamController::class);

        // 教師機能（担当生徒の成績閲覧・FB）
        Route::prefix('teacher')->name('teacher.')->group(function () {
            Route::get('/students/progress', [StudentProgressController::class, 'index'])->name('students.progress');
            Route::post('/students/feedback', [StudentProgressController::class, 'storeFeedback'])->name('students.feedback.store');
            Route::post('/students/points/reset', [StudentProgressController::class, 'resetAllStudentPoints'])->name('students.points.reset');
            Route::patch('/students/points/reset-interval', [StudentProgressController::class, 'updatePointResetInterval'])->name('students.points.reset-interval');
        });
    });

    // 管理者機能
    Route::middleware('role.hierarchy:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/import/template', [UserManagementController::class, 'downloadImportTemplate'])->name('users.import.template');
        Route::post('/users/import', [UserManagementController::class, 'import'])->name('users.import');
        Route::get('/users/accuracy', [UserManagementController::class, 'accuracy'])->name('users.accuracy');

        Route::get('/teacher-students', [UserManagementController::class, 'assignments'])->name('teacher-students.index');
        Route::post('/teacher-students', [UserManagementController::class, 'storeAssignment'])->name('teacher-students.store');
        Route::delete('/teacher-students/{teacher}/{student}', [UserManagementController::class, 'destroyAssignment'])
            ->name('teacher-students.destroy');
    });

    // 試験出題
    Route::get('/quiz', [QuizController::class, 'selectExam'])->name('quiz.select_exam');
    Route::get('/quiz/{exam}/resume', [QuizController::class, 'resume'])->name('quiz.resume');
    Route::get('/quiz/{exam}', [QuizController::class, 'index'])->name('quiz.index');
    Route::get('/quiz/{exam}/result', [QuizController::class, 'examResult'])->name('quiz.exam_result');
    Route::post('/quiz/{exam}/next', [QuizController::class, 'next'])->name('quiz.next');
    Route::post('/quiz/{exam}/answer', [QuizController::class, 'answer'])->name('quiz.answer');

    // フラグ管理
    Route::post('/questions/{question}/toggle-flag', [FlagController::class, 'toggle'])
    ->middleware('auth')
    ->name('questions.toggle_flag');
});

require __DIR__.'/auth.php';
