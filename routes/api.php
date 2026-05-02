<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search', [SearchController::class, 'index'])
    ->middleware('throttle:api.search')
    ->name('api.search');
