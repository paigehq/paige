<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\PageHistoryController;
use App\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('s')->group(function () {
    Route::get('{space:slug}', [SpaceController::class, 'show'])->name('spaces.show');

    Route::scopeBindings()->group(function () {
        Route::get('{space:slug}/{page:slug}', [PageController::class, 'show'])->name('pages.show');
    });

    Route::middleware('auth')->scopeBindings()->group(function () {
        Route::get('{space:slug}/new', [PageController::class, 'create'])->name('pages.create');
        Route::post('{space:slug}/pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('{space:slug}/{page:slug}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('{space:slug}/{page:slug}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('{space:slug}/{page:slug}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::get('{space:slug}/{page:slug}/history', [PageHistoryController::class, 'index'])->name('pages.history');
        Route::get('{space:slug}/{page:slug}/history/{revision}', [PageHistoryController::class, 'show'])->name('pages.history.show');
        Route::get('{space:slug}/{page:slug}/history/{a}/diff/{b}', [PageHistoryController::class, 'diff'])->name('pages.history.diff');
    });
});
