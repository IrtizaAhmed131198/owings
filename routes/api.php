<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CategoriesController;

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

Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);

Route::get('get-users', [AuthController::class, 'getUsers']);
Route::post('update-email', [AuthController::class, 'updateEmail']);
Route::get('delete-user/{id}', [AuthController::class, 'deleteUser']);

Route::get('categories/{data?}', [CategoriesController::class, 'getAllCategories']);
Route::get('categories/{id}/subcategories', [CategoriesController::class, 'getSubcategoriesByCategory']);


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

    Route::middleware('checkrole:2,3')->group(function () {
        Route::post('edit-profile', [AuthController::class, 'editProfile']);
        Route::get('get-edit-profile', [AuthController::class, 'getEditProfile']);
    });

});

