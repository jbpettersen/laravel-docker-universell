<?php

use Illuminate\Support\Facades\Route;
use Modules\Kalkyle\Http\Controllers\KalkyleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('kalkyles', KalkyleController::class)->names('kalkyle');
});
