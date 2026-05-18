<?php

// web.php//

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminActivationController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AdminQuizController;
use App\Http\Controllers\TeacherAuthController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherQuizController;
use App\Http\Controllers\AiQuizController;
use App\Http\Controllers\TeacherQuizAnalyticsController;
use App\Http\Controllers\AdminAnalyticsController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::prefix('admin')->group(function () {
    // Guest routes
    Route::middleware('admin.guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');



        Route::get('/activation', function () {
    return redirect()->route('admin.dashboard', [
        'type' => 'teacher',
        'status' => 'all',
    ]);
})->name('admin.activation.index');

Route::patch('/activation/{user}/activate', [AdminActivationController::class, 'activate'])->name('admin.activation.activate');
Route::patch('/activation/{user}/deactivate', [AdminActivationController::class, 'deactivate'])->name('admin.activation.deactivate');
        Route::get('/profile', [AdminProfileController::class, 'index'])->name('admin.profile');
        Route::post('/profile', [AdminProfileController::class, 'update'])->name('admin.profile.update');

        Route::get('/classes', [AdminQuizController::class, 'index'])
        ->name('admin.classes');

        Route::post('/classes', [AdminQuizController::class, 'store'])
            ->name('admin.classes.store');

        Route::get('/classes/{id}/details', [AdminQuizController::class, 'details'])
            ->name('admin.classes.details');

        Route::get('/classes/{classId}/quizzes/{quizId}/details', [AdminQuizController::class, 'quizDetails'])
            ->name('admin.classes.quizzes.details');

        Route::get('/classes/{classId}/quizzes/{quizId}/export-results', [AdminQuizController::class, 'exportResults'])
            ->name('admin.classes.quizzes.export.results');

        Route::get('/classes/{classId}/quizzes/{quizId}/export-analytics', [AdminQuizController::class, 'exportAnalytics'])
            ->name('admin.classes.quizzes.export.analytics');

        Route::get('/classes/{id}', [AdminQuizController::class, 'show']);
        Route::put('/classes/{id}', [AdminQuizController::class, 'update']);
        Route::delete('/classes/{id}', [AdminQuizController::class, 'destroy']);

         // ── Analytics ────────────────────────────────────────────
        Route::prefix('analytics')->name('admin.analytics.')->group(function () {
            Route::get('/',                       [AdminAnalyticsController::class, 'overview'])->name('overview');
            Route::get('/students',               [AdminAnalyticsController::class, 'students'])->name('students');
            Route::get('/students/export',        [AdminAnalyticsController::class, 'exportStudents'])->name('students.export');
            Route::get('/students/{user}',        [AdminAnalyticsController::class, 'studentShow'])->name('students.show');
            Route::get('/students/{user}/export', [AdminAnalyticsController::class, 'exportStudentProfile'])->name('students.show.export');
            Route::get('/quizzes',                [AdminAnalyticsController::class, 'quizzes'])->name('quizzes');
            Route::get('/quizzes/export',         [AdminAnalyticsController::class, 'exportQuizzes'])->name('quizzes.export');
            Route::get('/quizzes/{quiz}',          [AdminAnalyticsController::class, 'quizShow'])->name('quizzes.show');
            Route::get('/classes',                [AdminAnalyticsController::class, 'classes'])->name('classes');
            Route::get('/classes/export',         [AdminAnalyticsController::class, 'exportClasses'])->name('classes.export');
            Route::get('/teachers',               [AdminAnalyticsController::class, 'teachers'])->name('teachers');
            Route::get('/teachers/export',        [AdminAnalyticsController::class, 'exportTeachers'])->name('teachers.export');
            Route::get('/teachers/{user}',        [AdminAnalyticsController::class, 'teacherShow'])->name('teachers.show');
            Route::get('/teachers/{user}/export', [AdminAnalyticsController::class, 'exportTeacherProfile'])->name('teachers.show.export');
            Route::get('/classes/{classroom}',        [AdminAnalyticsController::class, 'classShow'])->name('classes.show');
            Route::get('/classes/{classroom}/export', [AdminAnalyticsController::class, 'exportClassShow'])->name('classes.show.export');
        });
    });
});


Route::prefix('teacher')->group(function () {
   Route::middleware('guest')->group(function () {
       Route::get('/login', [TeacherAuthController::class, 'showLoginForm'])->name('teacher.login');
       Route::post('/login', [TeacherAuthController::class, 'login'])->name('teacher.login.submit');
   });


   Route::middleware(['auth', 'teacher'])->group(function () {
       Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('teacher.logout');


       Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');


       Route::get('/reports/classes', [TeacherDashboardController::class, 'classes'])->name('teacher.reports.classes');
       Route::get('/reports/classes/{classId}', [TeacherDashboardController::class, 'classDetail'])->name('teacher.reports.class.detail');
       Route::get('/reports/classes/{classId}/export', [TeacherDashboardController::class, 'exportClassDetail'])->name('teacher.reports.class.export');
       Route::get('/reports/classes/{classId}/quizzes', [TeacherDashboardController::class, 'classQuizzes'])->name('teacher.reports.class.quizzes');
       Route::get('/reports/classes/{classId}/quizzes/{quizId}', [TeacherDashboardController::class, 'classQuizDetail'])->name('teacher.reports.class.quiz.detail');
       Route::get('/reports/classes/{classId}/quizzes/{quizId}/export', [TeacherDashboardController::class, 'exportClassQuizDetail'])->name('teacher.reports.class.quiz.detail.export');
       Route::get('/reports/quizzes', [TeacherDashboardController::class, 'quizzes'])->name('teacher.reports.quizzes');
       Route::get('/reports/quizzes/{quizId}/questions', [TeacherDashboardController::class, 'quizQuestions'])->name('teacher.reports.quiz.questions');
       Route::get('/reports/quizzes/{quizId}/questions/export-docx', [TeacherDashboardController::class, 'exportQuizQuestionsDocx'])->name('teacher.reports.quiz.questions.export.docx');
       Route::get('/reports/quizzes/{quizId}/questions/export-pdf', [TeacherDashboardController::class, 'exportQuizQuestionsPdf'])->name('teacher.reports.quiz.questions.export.pdf');
       Route::get('/reports/quizzes/{quizId}/answers', [TeacherDashboardController::class, 'quizAnswers'])->name('teacher.reports.quiz.answers');
       Route::get('/reports/quizzes/{quizId}/answers/export-docx', [TeacherDashboardController::class, 'exportQuizAnswersDocx'])->name('teacher.reports.quiz.answers.export.docx');
       Route::get('/reports/quizzes/{quizId}/answers/export-pdf', [TeacherDashboardController::class, 'exportQuizAnswersPdf'])->name('teacher.reports.quiz.answers.export.pdf');
       Route::get('/reports/students', [TeacherDashboardController::class, 'students'])->name('teacher.reports.students');
       Route::get('/reports/students/export', [TeacherDashboardController::class, 'exportStudents'])->name('teacher.reports.students.export');
       Route::get('/reports/students/{studentId}/classes/{classId}', [TeacherDashboardController::class, 'studentQuizInfo'])->name('teacher.reports.student.quiz.info');
       Route::get('/reports/students/{studentId}/classes/{classId}/export', [TeacherDashboardController::class, 'exportStudentQuizInfo'])->name('teacher.reports.student.quiz.info.export');


       // Analytics
       Route::get('/quizzes/{quizId}/analytics', [TeacherQuizAnalyticsController::class, 'quizAnalytics'])->name('teacher.analytics.quiz');
       Route::get('/analytics', [TeacherQuizAnalyticsController::class, 'globalAnalytics'])->name('teacher.analytics.global');


       // Quiz Management
       Route::get('/quizzes', [TeacherQuizController::class, 'index'])->name('teacher.quizzes.index');
       Route::get('/quizzes/create', [TeacherQuizController::class, 'create'])->name('teacher.quizzes.create');
       Route::post('/quizzes', [TeacherQuizController::class, 'store'])->name('teacher.quizzes.store');
       Route::get('/quizzes/{quizId}/manage', [TeacherQuizController::class, 'manage'])->name('teacher.quizzes.manage');
       Route::put('/quizzes/{quizId}', [TeacherQuizController::class, 'update'])->name('teacher.quizzes.update');
       Route::post('/quizzes/{quizId}/toggle-publish', [TeacherQuizController::class, 'togglePublish'])->name('teacher.quizzes.toggle-publish');
       Route::get('/quizzes/{quizId}/questions/create', [TeacherQuizController::class, 'createQuestion'])->name('teacher.quizzes.questions.create');
       Route::post('/quizzes/{quizId}/questions', [TeacherQuizController::class, 'storeQuestion'])->name('teacher.quizzes.questions.store');
       Route::get('/quizzes/{quizId}/questions/{questionId}/edit', [TeacherQuizController::class, 'editQuestion'])->name('teacher.quizzes.questions.edit');
       Route::put('/quizzes/{quizId}/questions/{questionId}', [TeacherQuizController::class, 'updateQuestion'])->name('teacher.quizzes.questions.update');
       Route::delete('/quizzes/{quizId}/questions/{questionId}', [TeacherQuizController::class, 'destroyQuestion'])->name('teacher.quizzes.questions.destroy');


       // AI Quiz Generation (Web)
       Route::post('/quizzes/ai/generate', [AiQuizController::class, 'generate'])->name('teacher.quizzes.ai.generate');
       Route::post('/quizzes/{quizId}/ai/save-questions', [AiQuizController::class, 'saveQuestions'])->name('teacher.quizzes.ai.save');

   });
});
