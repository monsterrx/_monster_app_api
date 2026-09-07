<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\ChartController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LiveController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PodcastController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShowController;
use App\Http\Controllers\Api\YoutubeController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home.index');
    Route::get('assets/{id}', [AssetController::class, 'show'])->whereNumber('id')->name('assets.show');
    Route::get('live', [LiveController::class, 'index'])->name('live.index');
    Route::get('charts', [ChartController::class, 'index'])->name('charts.index');
    Route::post('charts/votes', [ChartController::class, 'store'])->middleware('throttle:5,1')->name('charts.votes.store');
    Route::get('charts/vote', [ChartController::class, 'store'])->middleware('throttle:5,1')->name('charts.votes.legacy');
    Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/{id}', [ArticleController::class, 'show'])->whereNumber('id')->name('articles.show');
    Route::get('podcasts', [PodcastController::class, 'index'])->name('podcasts.index');
    Route::get('podcasts/{id}', [PodcastController::class, 'show'])->whereNumber('id')->name('podcasts.show');
    Route::get('shows', [ShowController::class, 'index'])->name('shows.index');
    Route::get('shows/{id}', [ShowController::class, 'show'])->whereNumber('id')->name('shows.show');
    Route::get('show/{id}', [ShowController::class, 'podcasts'])->whereNumber('id')->name('shows.podcasts.index');
    Route::get('youtube/{max}', [YoutubeController::class, 'index'])->whereNumber('max')->name('youtube.index');
    Route::get('search', [SearchController::class, 'index'])->name('search.index');
    Route::get('browse', [SearchController::class, 'browse'])->name('search.browse');
    Route::get('help', [PageController::class, 'help'])->name('pages.help');
    Route::get('about', [PageController::class, 'about'])->name('pages.about');
});
