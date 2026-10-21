<?php

use App\Http\Controllers\Api\AndonController;
use App\Http\Controllers\Api\CellInformationController;
use App\Http\Controllers\Api\DataPressureController;
use App\Http\Controllers\Api\HumidityController;
use App\Http\Controllers\Api\PassRateController;
use App\Http\Controllers\Api\PQIController;
use App\Http\Controllers\Api\whatsappController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/status_pqi/{id_device?}', [PQIController::class, 'getDataPQI']);
Route::get('/update_status/{id_device?}', [PQIController::class, 'updateStatus']);

Route::get('/pqi_data/{date}', [PQIController::class, 'data']);
Route::post('/pqi_data/export', [PQIController::class, 'export_data'])->name('api.pqi_data.export');

Route::middleware('apikey')->group(function () {
    Route::post('/wa/save-group', [whatsappController::class, 'saveGroup']);
});
