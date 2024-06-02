<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('get-countries', [AdminController::class, 'getCountries']);
Route::get('get-cities/{country_id?}', [AdminController::class, 'getCities']);

Route::middleware([ApiAuthenticate::class])->group(function () {

    Route::middleware('checkrole:1')->group(function () {
        Route::post('create-countries', [AdminController::class, 'createCountries']);
        Route::post('create-cities', [AdminController::class, 'createCities']);
    });
    
    Route::middleware('checkrole:2')->group(function () {
        // Merchant routes here
    });
    
    Route::middleware('checkrole:3')->group(function () {
        // Customer routes here
    });

});

