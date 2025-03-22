<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ImportTestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

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

Route::get('/', [HealthCheckController::class, 'index']);

Route::get('/test-import/{filename}', [ImportTestController::class, 'import']);
Route::get('test-import-job/{filename}', [ImportTestController::class, 'importJob']);

use JsonMachine\JsonMachine;

Route::get('/test-jsonmachine', function () {
    return response()->json(['loaded' => class_exists('JsonMachine\JsonMachine')]);
});



Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']); 
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{code}', [ProductController::class, 'show']); 
    Route::put('/{code}', [ProductController::class, 'update']); 
    Route::delete('/{code}', [ProductController::class, 'destroy']);
});
