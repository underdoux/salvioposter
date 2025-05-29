<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ScheduledPostController;
use App\Http\Controllers\ContentGeneratorController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])
    ->name('auth.google');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Protected Routes
Route::middleware(['auth', \App\Http\Middleware\ValidOAuthToken::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return response('Dashboard - Welcome ' . auth()->user()->name);
    })->name('dashboard');

    // Post Management Routes
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Post Publishing and Preview
    Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
    Route::get('/posts/{post}/preview', [PostController::class, 'preview'])->name('posts.preview');

    // Content Generation Routes
    Route::prefix('content-generator')->name('content.')->group(function () {
        Route::post('/titles', [ContentGeneratorController::class, 'generateTitles'])->name('titles');
        Route::post('/content', [ContentGeneratorController::class, 'generateContent'])->name('generate');
        Route::post('/post', [ContentGeneratorController::class, 'generatePost'])->name('post');
    });

    // Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/posts/{post}', [AnalyticsController::class, 'show'])->name('show');
        Route::post('/posts/{post}/sync', [AnalyticsController::class, 'sync'])->name('sync');
        Route::get('/chart-data', [AnalyticsController::class, 'getChartData'])->name('chart-data');
        Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
        Route::get('/posts/{post}/report', [AnalyticsController::class, 'generateReport'])->name('report');
    });

    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread/count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::post('/clear-all', [NotificationController::class, 'clearAll'])->name('clear-all');
    });

    // Scheduled Posts Routes
    Route::get('/scheduled-posts', [ScheduledPostController::class, 'index'])->name('scheduled.index');
    Route::post('/posts/{post}/schedule', [ScheduledPostController::class, 'store'])->name('scheduled.store');
    Route::put('/scheduled-posts/{scheduledPost}', [ScheduledPostController::class, 'update'])->name('scheduled.update');
    Route::delete('/scheduled-posts/{scheduledPost}', [ScheduledPostController::class, 'destroy'])->name('scheduled.destroy');
    Route::post('/scheduled-posts/{scheduledPost}/retry', [ScheduledPostController::class, 'retry'])->name('scheduled.retry');
});

// Logout Route
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');
