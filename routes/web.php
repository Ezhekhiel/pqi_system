<?php

use App\Http\Controllers\AreaDbPqiController;
use App\Http\Controllers\DatabaseAreaController;
use App\Http\Controllers\pqiController;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', function () {
    return view('welcome');
});
Route::get('/pqi', [pqiController::class, 'index']);
Route::get('/pqi/export', [pqiController::class, 'export'])->name('pqi.export');

Route::get('/PQI/Manage Device', [AreaDbPqiController::class, 'manage_device']);
Route::get('/PQI/manage/device_IoT/main', [AreaDbPqiController::class, 'main'])->name('manage.pqi.device.main');
Route::get('/PQI/manage/device_IoT/scan_barcode', [AreaDbPqiController::class, 'scan_barcode'])->name('manage.pqi.device.scan_barcode');
Route::get('/PQI/manage/device_IoT/delete_device', [AreaDbPqiController::class, 'delete_device'])->name('manage.pqi.device.delete_device');
Route::get('/IOT/manage/device_IoT/update_unit_proses', [AreaIotDbController::class, 'update_unit_proses'])->name('manage.device_IoT.update_unit_proses');


Route::get('/Manage/Location', [DatabaseAreaController::class, 'index']);

Route::get('/manage/area/main', [DatabaseAreaController::class, 'main'])->name('manage.area.main');
Route::post('/manage/area/save_location', [DatabaseAreaController::class, 'save_location'])->name('manage.area.save_location');
Route::post('/manage/area/save_area', [DatabaseAreaController::class, 'save_area'])->name('manage.area.save_area');
Route::post('/manage/area/save_bagian', [DatabaseAreaController::class, 'save_bagian'])->name('manage.area.save_bagian');
Route::get('/manage/area/deleteID', [DatabaseAreaController::class, 'deleteID'])->name('manage.area.deleteID');
Route::get('/manage/area/location_change', [DatabaseAreaController::class, 'location_change'])->name('manage.area.location_change');

require __DIR__.'/auth.php';
