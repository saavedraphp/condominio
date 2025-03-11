<?php
use App\Http\Controllers\Auth\LoginUserController;

use App\Http\Controllers\User\DashboardController as UserDashboard;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\BathroomsController;
/*Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest');*/

Route::get('/', [LoginUserController::class, 'showLoginForm']);
Route::post('/login', [LoginUserController::class, 'authentication'])->name('login');
Route::post('/logout', [LoginUserController::class, 'logout'])->name('logout');

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

Route::middleware(['auth'])->group(function () {
    Route::get('/user/dashboard', [UserDashboard::class, 'index'])->name('user.dashboard');
});

Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('admin.dashboard');
    Route::get('/servicios/baños', [BathroomsController::class, 'index'])->name('admin.servicios.baños');
});
