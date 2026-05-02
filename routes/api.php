<?php

use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search', [SearchController::class, 'index'])
    ->middleware('throttle:api.search')
    ->name('api.search');
