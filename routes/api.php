<?php

use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/search', [SearchController::class, 'index'])
    ->middleware('throttle:api.search')
    ->name('api.search');

Route::middleware('throttle:60,1')
    ->get('/tags', [TagController::class, 'index'])
    ->name('api.tags');
