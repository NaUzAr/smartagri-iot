<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SensorDataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes untuk menerima data sensor dari device IoT
|
*/

// POST /api/sensor-data - Terima data sensor baru
Route::post('/sensor-data', [SensorDataController::class, 'store']);

// GET /api/sensor-data/{token} - Ambil data terbaru
Route::get('/sensor-data/{token}', [SensorDataController::class, 'show']);
