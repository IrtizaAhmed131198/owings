<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::group(['middleware' => ['jwt.auth', 'role:1']], function () {
    // Admin routes here
    Route::get('/admin', function () {
        return response()->json(['message' => 'Welcome, Admin']);
    });
});

Route::group(['middleware' => ['jwt.auth', 'role:2']], function () {
    // Merchant routes here
    Route::get('/merchant', function () {
        return response()->json(['message' => 'Welcome, Merchant']);
    });
});

Route::group(['middleware' => ['jwt.auth', 'role:3']], function () {
    // Customer routes here
    Route::get('/customer', function () {
        return response()->json(['message' => 'Welcome, Customer']);
    });
});

