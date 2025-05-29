<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::post('posts', [PostController::class, 'store'])->name('api.posts.store');
    Route::put('posts/{post}', [PostController::class, 'update'])->name('api.posts.update');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('api.posts.destroy');
    Route::post('posts/{post}/publish', [PostController::class, 'publish'])->name('api.posts.publish');
});
