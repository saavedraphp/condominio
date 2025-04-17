<?php


use App\Http\Controllers\Admin\UserHouseAssignmentController;
use App\Http\Controllers\User\Auth\LoginUserController;
use App\Http\Controllers\Admin\Auth\LoginUserController as AdminLogin;

use App\Http\Controllers\Admin\UserSettingPageController;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\User\DashboardController as UserDashboard;
use App\Http\Controllers\User\HouseController;
use App\Http\Controllers\User\HouseResidentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\VehicleController;
use App\Http\Controllers\User\AdsController as UserAdsController;
use App\Http\Controllers\AccountActivationController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\HouseController as AdminHouseController;
use App\Http\Controllers\Admin\AdsController;
use App\Http\Controllers\Admin\UserController as AdminUserAdsController;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\BathroomsController;

Route::get('/', [HomeController::class, 'showLoginType']);




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

// web_user Auth Routes
Route::prefix('user')->name('user.')->group(function () {
    Route::get('/login', [LoginUserController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginUserController::class, 'authentication']);
    Route::get('/logout', [LoginUserController::class, 'logout'])->name('logout');

    Route::get('/activar-cuenta/{token}', [AccountActivationController::class, 'showActivationForm'])->name('account.activate.form');
    Route::post('/activar-cuenta', [AccountActivationController::class, 'activate'])->name('account.activate');


    Route::middleware('auth:web_user')->group(function () {
        Route::get('/dashboard', [UserDashboard::class, 'index'])->name('dashboard');
        Route::get('/get-user-data', [ProfileController::class, 'getUserData'])->name('user.profile.show');
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::get('/ads', [UserAdsController::class, 'index']);
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::resource('/profile', ProfileController::class);

        Route::resource('/vehicles', VehicleController::class);
        Route::get('/get-vehicles-data/{userId}', [VehicleController::class, 'getVehiclesByUserId'])->name('user.vehicles.list');


        Route::get('/get-house/{house}', [HouseController::class, 'getHouse'])->name('user.house.show');
        Route::get('/houses', [HouseController::class, 'houses'])->name('user.house.list');
        Route::post('/house/{house}', [HouseController::class, 'update'])->name('user.house.update');

        Route::resource('/house-residents', HouseResidentController::class);
        Route::get('/get-house-residents-data/{houseId}', [HouseResidentController::class, 'getHouseResidentsData'])->name('user.house.listResidents');

    });

});

/*RUTAS DEL ADMIN*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLogin::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLogin::class, 'authentication']);
    Route::get('/logout', [AdminLogin::class, 'logout'])->name('logout');

    Route::middleware(['auth:web'])->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
        Route::get('/ads/list', [AdsController::class, 'showListPage'])->name('ads.list');
        Route::resource('/ads', AdsController::class);

        Route::get('/users/list', [AdminUserAdsController::class, 'showListPage'])->name('user.list');
        Route::resource('/users', AdminUserAdsController::class);
        Route::get('/users/{webUser}/settings', UserSettingPageController::class);

        Route::prefix('/user/{webUser}/house-assignments/')->name('users.house-assignments.')->group(function () {
            Route::get('/', [UserHouseAssignmentController::class, 'index'])->name('index');
            Route::get('/getUnassigned', [UserHouseAssignmentController::class, 'getUnassigned'])->name('getUnassigned');
            Route::post('/', [UserHouseAssignmentController::class, 'store'])->name('store');
            Route::delete('/{house}', [UserHouseAssignmentController::class, 'destroy'])->name('destroy');
        });


        /*LIST HOUSES*/
        Route::get('/houses/list', [AdminHouseController::class, 'showListPage'])->name('houses.list');
        Route::resource('/houses', AdminHouseController::class);
    });

});

