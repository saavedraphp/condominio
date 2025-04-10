<?php

use App\Http\Controllers\Auth\LoginUserController;

use App\Http\Controllers\User\DashboardController as UserDashboard;
use App\Http\Controllers\User\HouseController;
use App\Http\Controllers\User\HouseResidentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\VehicleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\AdsController;
use App\Http\Controllers\Admin\UserController as AdminUserAdsController;
use App\Http\Controllers\User\AdsController as UserAdsController;
use App\Http\Controllers\AccountActivationController;


use App\Http\Controllers\Admin\BathroomsController;

/*Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest');*/

Route::get('/', [LoginUserController::class, 'showLoginForm']);
Route::post('/login', [LoginUserController::class, 'authentication']);
Route::post('/logout', [LoginUserController::class, 'logout'])->name('logout');

Route::get('/login', function () {
    return view('auth.login'); // Reemplaza con tu vista de login
})->name('login');

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


Route::get('/home', function () {
    return view('home');
});

Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboard::class, 'index'])->name('dashboard');
    Route::get('/ads', [UserAdsController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

});

Route::get('/user/get-user-data', [ProfileController::class, 'getUserData'])->name('user.profile.show');
Route::resource('/user/profile', ProfileController::class);

Route::get('/user/get-house/{house}', [HouseController::class, 'getHouse'])->name('user.house.show');
Route::post('/user/house/{house}', [HouseController::class, 'update'])->name('user.house.update');

Route::resource('/user/house-residents', HouseResidentController::class);
Route::get('/user/get-house-residents-data/{houseId}', [HouseResidentController::class, 'getHouseResidentsData'])->name('user.house.listResidents');


Route::resource('/user/vehicles', VehicleController::class);
Route::get('/user/get-vehicles-data/{userId}', [VehicleController::class, 'getVehiclesByUserId'])->name('user.vehicles.listVehicles');

/*RUTAS DEL ADMIN*/
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    Route::get('/ads/list', [AdsController::class, 'showListPage'])->name('ads.list');
    Route::resource('/ads', AdsController::class);

    Route::get('/users/list', [AdminUserAdsController::class, 'showListPage'])->name('user.list');
    Route::resource('/users', AdminUserAdsController::class);

});

Route::get('/activar-cuenta/{token}', [AccountActivationController::class, 'showActivationForm'])->name('account.activate.form');
Route::post('/activar-cuenta', [AccountActivationController::class, 'activate'])->name('account.activate');
