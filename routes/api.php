<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PodcastController;
use App\Http\Controllers\ShowController;

use Illuminate\Support\Facades\Route;

Route::prefix('mobile_app_v2')->group(function() {
    Route::get('', [HomeController::class, 'index'])->name('mobile_app.home');
    Route::get('assets/{id}', [HomeController::class, 'assets'])->name('mobile_app.assets');
    Route::get('live', [LiveStreamController::class, 'index'])->name('mobile_app.live');
    Route::get('live/show', [LiveStreamController::class, 'getLiveShow'])->name('mobile_app.live_show');

    Route::get('articles', [ArticleController::class, 'index'])->name('mobile_app.articles');
    Route::get('articles/{id}', [ArticleController::class, 'show'])->name('mobile.app_show_article');
    Route::get('categories', [CategoryController::class, 'index'])->name('mobile_app.categories');

    Route::get('charts', [ChartController::class, 'index'])->name('mobile_app.charts');
    Route::get('charts/vote', [ChartController::class, 'vote'])->name('mobile_app.vote');

    Route::get('podcasts', [PodcastController::class, 'index'])->name('mobile_app.podcasts');
    Route::get('podcasts/{id}', [PodcastController::class, 'show'])->name('mobile_app.show_podcast');

    Route::get('shows', [ShowController::class, 'index'])->name('mobile_app.shows');
    Route::get('shows/{id}', [ShowController::class, 'show'])->name('mobile_app.show_program');    
});