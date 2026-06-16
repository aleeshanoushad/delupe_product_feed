<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(ApiKeyMiddleware::class)->prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/summary', [ProductController::class, 'summary']);
    Route::get('/duplicates', [ProductController::class, 'duplicates']);
});
