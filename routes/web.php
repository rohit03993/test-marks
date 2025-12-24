<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Students routes
    Route::resource('students', \App\Http\Controllers\StudentController::class)->except(['show', 'create', 'edit', 'update', 'destroy']);
    Route::get('/students/upload', [\App\Http\Controllers\StudentController::class, 'showUpload'])->name('students.upload');
    Route::post('/students/upload', [\App\Http\Controllers\StudentController::class, 'upload'])->name('students.upload.store');
    Route::post('/students/get-headers', [\App\Http\Controllers\StudentController::class, 'getHeaders'])->name('students.get-headers');
    Route::get('/students/{student}/profile', [\App\Http\Controllers\StudentController::class, 'profile'])->name('students.profile');
    Route::put('/exam-results/{examResult}/update-marks', [\App\Http\Controllers\StudentController::class, 'updateExamMarks'])->name('exam-results.update-marks');
    Route::delete('/exam-results/{examResult}', [\App\Http\Controllers\StudentController::class, 'deleteExamResult'])->name('exam-results.delete');
    
    // Search routes
    Route::get('/search', [\App\Http\Controllers\StudentController::class, 'search'])->name('search.index');
    Route::post('/search', [\App\Http\Controllers\StudentController::class, 'performSearch'])->name('search.perform');
    
    // Bulk class assignment
    Route::get('/students/bulk-assign', [\App\Http\Controllers\ClassStudentController::class, 'showBulkAssign'])->name('students.bulk-assign');
    Route::post('/students/bulk-assign', [\App\Http\Controllers\ClassStudentController::class, 'bulkAssign'])->name('students.bulk-assign.store');

    // Classes routes
    Route::resource('classes', \App\Http\Controllers\ClassController::class)->except(['show']);

    // Exams routes
    Route::resource('exams', \App\Http\Controllers\ExamController::class)->except(['show']);
    Route::post('/exams/create-subject', [\App\Http\Controllers\ExamController::class, 'createSubject'])->name('exams.create-subject');
    
    // Exam Results routes
    Route::get('/exams/{exam}/upload-results', [\App\Http\Controllers\ExamResultController::class, 'showUpload'])->name('exams.upload-results');
    Route::post('/exams/{exam}/upload-results', [\App\Http\Controllers\ExamResultController::class, 'upload'])->name('exams.upload-results.store');
    Route::get('/exams/{exam}/view-results', [\App\Http\Controllers\ExamController::class, 'viewResults'])->name('exams.view-results');
    Route::post('/exams/get-headers', [\App\Http\Controllers\ExamResultController::class, 'getHeaders'])->name('exams.get-headers');
    
    // Results routes
    Route::get('/results', [\App\Http\Controllers\ResultsController::class, 'index'])->name('results.index');
    Route::get('/results/class/{class}', [\App\Http\Controllers\ResultsController::class, 'showClass'])->name('results.class');
    Route::get('/results/class/{class}/export', [\App\Http\Controllers\ResultsController::class, 'exportClass'])->name('results.class.export');
    
    // Admin Reset routes
    Route::get('/admin/reset', [\App\Http\Controllers\ResetController::class, 'index'])->name('reset.index');
    Route::post('/admin/reset', [\App\Http\Controllers\ResetController::class, 'reset'])->name('reset.store');
});
