<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\PageHistoryController;
use App\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('s')->group(function () {
    Route::get('{space:slug}', [SpaceController::class, 'show'])->name('spaces.show');

    // Literal-segment routes must be registered before the {page:slug} wildcard
    // to prevent /new from being matched as a page slug.
    Route::middleware('auth')->group(function () {
        Route::get('{space:slug}/new', [PageController::class, 'create'])->name('pages.create');
        Route::post('{space:slug}/pages', [PageController::class, 'store'])->name('pages.store');
    });

    Route::scopeBindings()->group(function () {
        Route::get('{space:slug}/{page:slug}', [PageController::class, 'show'])->name('pages.show');
    });

    Route::middleware('auth')->scopeBindings()->group(function () {
        Route::get('{space:slug}/{page:slug}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('{space:slug}/{page:slug}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('{space:slug}/{page:slug}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::get('{space:slug}/{page:slug}/history', [PageHistoryController::class, 'index'])->name('pages.history');
        Route::get('{space:slug}/{page:slug}/history/{revision}', [PageHistoryController::class, 'show'])->name('pages.history.show');
        Route::get('{space:slug}/{page:slug}/history/{a}/diff/{b}', [PageHistoryController::class, 'diff'])->name('pages.history.diff');
    });
});
