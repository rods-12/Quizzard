<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\AiQuizController;



// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/upload-image', [ImageController::class, 'upload']);
    Route::post('/upload-video', [VideoController::class, 'upload']);
    Route::post('/upload-audio', [AudioController::class, 'upload']);

    // Student routes
    Route::get('/student/dashboard', [StudentController::class, 'dashboard']);
    Route::get('/student/classes', [StudentController::class, 'myClasses']);
    Route::post('/student/classes/join', [StudentController::class, 'joinClass']);
    Route::delete('/student/classes/{classId}/leave', [StudentController::class, 'leaveClass']);
    Route::get('/student/classes/{classId}/quizzes', [StudentController::class, 'classQuizzes']);
    Route::get('/student/profile', [StudentController::class, 'getProfile']);
    Route::put('/student/profile', [StudentController::class, 'updateProfile']);
    Route::get('/student/quizzes', [StudentController::class, 'allQuizzes']);
    Route::get('/student/attempts/{attemptId}', [StudentController::class, 'getAttempt']); // Phase 5

    // Teacher routes
    Route::get('/teacher/dashboard', [TeacherController::class, 'dashboard']);
    Route::get('/teacher/quizzes/{quizId}/results', [TeacherController::class, 'quizResults']);
    Route::get('/teacher/quizzes/{quizId}/results/{attemptId}', [TeacherController::class, 'attemptDetail']);

    // Class routes
    Route::get('/classes', [ClassController::class, 'index']);
    Route::post('/classes', [ClassController::class, 'store']);
    Route::get('/classes/{classId}', [ClassController::class, 'show']);
    Route::put('/classes/{classId}', [ClassController::class, 'update']);
    Route::delete('/classes/{classId}', [ClassController::class, 'destroy']);
    Route::post('/classes/{classId}/assign-quiz', [ClassController::class, 'assignQuiz']);
    Route::delete('/classes/{classId}/quizzes/{quizId}', [ClassController::class, 'unassignQuiz']);
    Route::patch('/classes/{classId}/quizzes/{quizId}/due-date', [ClassController::class, 'updateDueDate']);
    Route::get('/classes/{classId}/quizzes/{quizId}/results', [ClassController::class, 'classQuizResults']);
    Route::get('/classes/{classId}/students/performance', [ClassController::class, 'studentPerformance']);
    Route::get('/classes/{classId}/students/export-performance', [ClassController::class, 'exportStudentPerformance']);

    // Quiz CRUD
    Route::get('/quizzes',                           [QuizController::class, 'index']);
    Route::post('/quizzes',                          [QuizController::class, 'store']);
    Route::get('/quizzes/{quizId}',                  [QuizController::class, 'show']);
    Route::put('/quizzes/{quizId}',                  [QuizController::class, 'update']);
    Route::delete('/quizzes/{quizId}',               [QuizController::class, 'destroy']);
    Route::patch('/quizzes/{quizId}/publish-toggle', [QuizController::class, 'publishToggle']);

    // Quiz taking
    Route::post('/quizzes/{quizId}/start',  [QuizController::class, 'startAttempt']);
    Route::post('/quizzes/{quizId}/submit', [QuizController::class, 'submitQuiz']);

    // Question routes
    Route::get('/quizzes/{quizId}/questions',                         [QuestionController::class, 'index']);
    Route::post('/quizzes/{quizId}/questions/multiple-choice',        [QuestionController::class, 'storeMultipleChoice']);
    Route::post('/quizzes/{quizId}/questions/true-false',             [QuestionController::class, 'storeTrueFalse']);
    Route::post('/quizzes/{quizId}/questions/identification',         [QuestionController::class, 'storeIdentification']);
    Route::post('/quizzes/{quizId}/questions/matching',               [QuestionController::class, 'storeMatching']);
    Route::put('/quizzes/{quizId}/questions/{questionId}',            [QuestionController::class, 'update']);
    Route::delete('/quizzes/{quizId}/questions/{questionId}',         [QuestionController::class, 'destroy']);

    // Analytics route
    Route::get('/teacher/quizzes/{quizId}/analytics', [TeacherController::class, 'quizAnalytics']);

    Route::get('/teacher/quizzes/{quizId}/export-results', [TeacherController::class, 'exportResults']);
    Route::get('/teacher/quizzes/{quizId}/export-analytics', [TeacherController::class, 'exportAnalytics']);
    Route::get('/teacher/quizzes/{quizId}/export-full', [TeacherController::class, 'exportFullReport']);
    Route::get('/teacher/classes/{classId}/quizzes/{quizId}/export-results', [TeacherController::class, 'exportClassQuizResults']);

    // AI Quiz Generation
    Route::post('/ai/generate-questions', [AiQuizController::class, 'generate']);
    Route::post('/ai/quizzes/{quizId}/save-questions', [AiQuizController::class, 'saveQuestions']);

});
