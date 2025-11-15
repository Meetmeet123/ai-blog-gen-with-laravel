<?php

use App\Http\Controllers\Admin\AiGenerationController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\PublicBlogController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicBlogController::class, 'index'])->name('blogs.public');
Route::get('/blog/{slug}', [PublicBlogController::class, 'show'])->name('blogs.show');

Auth::routes(['register' => false]);

Route::middleware('auth')
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('blogs/{blog}/preview', [BlogController::class, 'preview'])->name('blogs.preview');
        Route::resource('blogs', BlogController::class)->except(['show']);

        Route::post('ai/intros', [AiGenerationController::class, 'intros'])->name('ai.intros');
        Route::post('ai/slug', [AiGenerationController::class, 'slug'])->name('ai.slug');
        Route::post('ai/content', [AiGenerationController::class, 'content'])->name('ai.content');
        Route::post('ai/images', [AiGenerationController::class, 'images'])->name('ai.images');
    });
