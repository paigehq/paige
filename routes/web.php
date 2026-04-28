<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('s')->group(function () {
    Route::get('{space:slug}', [SpaceController::class, 'show'])->name('spaces.show');

    Route::scopeBindings()->group(function () {
        Route::get('{space:slug}/{page:slug}', [PageController::class, 'show'])->name('pages.show');
    });
});
