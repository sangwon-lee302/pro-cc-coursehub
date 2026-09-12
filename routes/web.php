<?php

use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CoachCourseController;
use App\Http\Controllers\CoachLessonController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseStudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MyCourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizManageController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard redirect based on role
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Student routes
    Route::middleware('role:student')->group(function () {
        Route::get('/my-courses', [MyCourseController::class, 'index'])->name('my-courses.index');
        Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');
        Route::post('/courses/{course}/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('courses.lessons.complete');
        Route::post('/courses/{course}/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('courses.quizzes.submit');
        Route::get('/courses/{course}/quizzes/{quiz}/result', [QuizController::class, 'result'])->name('courses.quizzes.result');
        Route::post('/courses/{course}/reviews', [ReviewController::class, 'store'])->name('courses.reviews.store');
    });

    // Course browsing (student + coach + admin can view)
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])->name('courses.lessons.show');
    Route::get('/courses/{course}/quizzes/{quiz}', [QuizController::class, 'show'])->name('courses.quizzes.show');

    // Coach routes
    Route::middleware('role:coach')->prefix('coach')->name('coach.')->group(function () {
        Route::get('/', [CoachCourseController::class, 'dashboard'])->name('dashboard');

        Route::get('/courses', [CoachCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/create', [CoachCourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CoachCourseController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}/edit', [CoachCourseController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [CoachCourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [CoachCourseController::class, 'destroy'])->name('courses.destroy');

        Route::get('/courses/{course}/chapters', [ChapterController::class, 'index'])->name('courses.chapters.index');
        Route::get('/courses/{course}/chapters/create', [ChapterController::class, 'create'])->name('courses.chapters.create');
        Route::post('/courses/{course}/chapters', [ChapterController::class, 'store'])->name('courses.chapters.store');
        Route::get('/courses/{course}/chapters/{chapter}/edit', [ChapterController::class, 'edit'])->name('courses.chapters.edit');
        Route::put('/courses/{course}/chapters/{chapter}', [ChapterController::class, 'update'])->name('courses.chapters.update');
        Route::delete('/courses/{course}/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('courses.chapters.destroy');
        Route::post('/courses/{course}/chapters/order', [ChapterController::class, 'updateOrder'])->name('courses.chapters.order');

        Route::get('/courses/{course}/chapters/{chapter}/lessons', [CoachLessonController::class, 'index'])->name('courses.chapters.lessons.index');
        Route::get('/courses/{course}/chapters/{chapter}/lessons/create', [CoachLessonController::class, 'create'])->name('courses.chapters.lessons.create');
        Route::post('/courses/{course}/chapters/{chapter}/lessons', [CoachLessonController::class, 'store'])->name('courses.chapters.lessons.store');
        Route::get('/courses/{course}/chapters/{chapter}/lessons/{lesson}/edit', [CoachLessonController::class, 'edit'])->name('courses.chapters.lessons.edit');
        Route::put('/courses/{course}/chapters/{chapter}/lessons/{lesson}', [CoachLessonController::class, 'update'])->name('courses.chapters.lessons.update');
        Route::delete('/courses/{course}/chapters/{chapter}/lessons/{lesson}', [CoachLessonController::class, 'destroy'])->name('courses.chapters.lessons.destroy');

        Route::get('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'index'])->name('courses.lessons.quizzes.index');
        Route::post('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'store'])->name('courses.lessons.quizzes.store');
        Route::put('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'update'])->name('courses.lessons.quizzes.update');
        Route::delete('/courses/{course}/lessons/{lesson}/quizzes', [QuizManageController::class, 'destroy'])->name('courses.lessons.quizzes.destroy');
        Route::post('/courses/{course}/lessons/{lesson}/quizzes/questions', [QuizManageController::class, 'storeQuestion'])->name('courses.lessons.quizzes.questions.store');
        Route::delete('/courses/{course}/lessons/{lesson}/quizzes/questions/{question}', [QuizManageController::class, 'destroyQuestion'])->name('courses.lessons.quizzes.questions.destroy');

        Route::get('/courses/{course}/students', [CourseStudentController::class, 'index'])->name('courses.students.index');
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');

        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');

        Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });
});
