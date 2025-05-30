<?php


use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\Admin\AdsController;
use App\Http\Controllers\Admin\Auth\LoginUserController as AdminLogin;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\DoormanController;
use App\Http\Controllers\Admin\HouseController as AdminHouseController;
use App\Http\Controllers\Admin\HouseResidentController;
use App\Http\Controllers\Admin\PetitionController as AdminPetitionController;
use App\Http\Controllers\Admin\UserController as AdminUserAdsController;
use App\Http\Controllers\Admin\UserHouseAssignmentController;
use App\Http\Controllers\Admin\UserSettingPageController;
use App\Http\Controllers\AnnualBudgetController;
use App\Http\Controllers\BudgetReportController;
use App\Http\Controllers\BudgetTypeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentServiceController;
use App\Http\Controllers\PetitionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectQuotationController;
use App\Http\Controllers\PublicStatusController;
use App\Http\Controllers\User\AdsController as UserAdsController;
use App\Http\Controllers\User\Auth\LoginUserController;
use App\Http\Controllers\User\DashboardController as UserDashboard;
use App\Http\Controllers\User\HouseController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;


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

Route::get('/version', function () {
    phpinfo();
})->name('home');
// web_user Auth Routes
Route::prefix('user')->name('user.')->group(function () {
    Route::get('/login', [LoginUserController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginUserController::class, 'authentication']);
    Route::get('/logout', [LoginUserController::class, 'logout'])->name('logout');

    Route::get('/activar-cuenta/{token}', [AccountActivationController::class, 'showActivationForm'])->name('account.activate.form');
    Route::post('/activar-cuenta', [AccountActivationController::class, 'activate'])->name('account.activate');


    Route::middleware('auth:web_user')->group(function () {
        Route::get('/my-qr-code', [UserDashboard::class, 'pageQrCode'])->name('user.my-qr-code');
        Route::get('/get-qr-code', [UserDashboard::class, 'generateQrCode'])->name('user.get-qrcode');

        Route::get('/dashboard', [UserDashboard::class, 'index'])->name('dashboard');
        Route::get('/get-user-data', [ProfileController::class, 'getUserData'])->name('user.profile.show');
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::get('/ads', [UserAdsController::class, 'index']);
        Route::resource('/profile', ProfileController::class);

        /* INICIO rutas documentos*/
        Route::get('/documents/list', [DocumentController::class, 'showPage'])->name('documents.show-page');
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])
            ->where('document', '[0-9]+'); // Asegura que el ID sea numérico

        // Ruta nombrada para la descarga segura
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
            ->name('documents.download') // Nombre de la ruta usado en el Modelo
            ->where('document', '[0-9]+');
        /* FIN rutas documentos*/

       // Route::resource('/vehicles', VehicleController::class);
        Route::get('/vehicles/list', [VehicleController::class, 'showPage'])->name('vehicles.list');
        Route::get('/vehicles/', [VehicleController::class, 'getVehicles'])->name('vehicles.getVehicles');

        Route::get('/houses/{house}/dashboard', [HouseController::class, 'dashboard'])->name('user.house.dashboard');
        Route::get('/houses/show/{house}', [HouseController::class, 'show'])->name('user.house.show');
        Route::get('/houses', [HouseController::class, 'index'])->name('user.house.index');
        Route::get('/houses/list', [HouseController::class, 'showPage'])->name('user.house.showPage');
        Route::post('/house/{house}', [HouseController::class, 'update'])->name('user.house.update');

        Route::get('/houses/{house}/payments/list', [PaymentController::class, 'showPage'])->name('user.show-page');
        Route::resource('/house/{house}/payments', PaymentController::class);

        Route::get('/payments/{payment}/download', [PaymentController::class, 'downloadPayment'])
            ->name('payments.download');

        // Descargar PDF resumen del año
        Route::get('/house/{house}/payments/{payment}/download-year/{year}', [PaymentController::class, 'downloadYearlyPdf'])
            ->name('payments.download.year');

        Route::get('/houses/{house}/electricity-records/list', [PaymentServiceController::class, 'showPage'])->name('houses.electricity-history.show-page');

        Route::get('/houses/{house}/water-records/list', [PaymentServiceController::class, 'showPageWater'])->name('houses.electricity-history.show-page-water');
        Route::get('/houses/{house}/consumption/', [PaymentServiceController::class,'index'])->name('houses.consumption.index');

        /* PETITICIONS */
        Route::get('/petitions/list', [PetitionController::class, 'showPage'])->name('petitions.show-page');;
        Route::get('/petitions', [PetitionController::class, 'index'])->name('petitions.index');
        Route::post('/petitions', [PetitionController::class, 'store'])->name('petitions.store');
        Route::get('/petitions/{petition}', [PetitionController::class, 'show'])->name('petitions.show');
        Route::post('/petitions/{petition}/replies', [PetitionController::class, 'addReply'])->name('petitions.replies.store');

        /*ANNUAL BUDGET VS EXPENSES*/
        Route::get('/budgets-vs-expenses/search', [BudgetReportController::class, 'showPage'])->name('budgets-vs-expenses.show-page');
        Route::get('/reports/budget-summary-data', [BudgetReportController::class, 'generateReportData'])->name('reports.budget.data');
        Route::get('/reports/budget-summary/download', [BudgetReportController::class, 'downloadPdfReport'])->name('reports.budget.pdf');
    });

});

/*RUTAS DEL ADMIN*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLogin::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLogin::class, 'authentication']);
    Route::get('/logout', [AdminLogin::class, 'logout'])->name('logout');

    Route::middleware(['auth:web'])->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

        /* ANUNCIOS */
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

        Route::prefix('/user/{webUser}/house/{house}/')->name('user.house.')->group(function () {
            Route::get('/members/list', [HouseResidentController::class, 'showListPage'])->name('member.list');
            Route::get('/house-residents', [HouseResidentController::class, 'index'])->name('members.index');
            Route::post('/house-residents', [HouseResidentController::class, 'store'])->name('members.store');
            Route::put('/house-residents/{houseResident}', [HouseResidentController::class, 'update'])->name('members.update');
            Route::delete('/house-residents/{houseResident}', [HouseResidentController::class, 'destroy'])->name('members.destroy');
/*            Route::get('/getUnassigned', [UserHouseAssignmentController::class, 'getUnassigned'])->name('getUnassigned');
            Route::post('/', [UserHouseAssignmentController::class, 'store'])->name('store');
            //Route::delete('/{house}', [UserHouseAssignmentController::class, 'destroy'])->name('destroy');
            ;*/

        });

        /* VEHICULOS */
        Route::get('/user/{webUser}/vehicles/', [VehicleController::class, 'index'])->name('user.vehicles.index');
        Route::post('/user/{webUser}/vehicles/', [VehicleController::class, 'store'])->name('user.vehicles.store');
        Route::put('/user/{webUser}/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('user.vehicles.update');
        Route::delete('/user/{webUser}/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('user.vehicles.destroy');
        //Route::get('/vehicles/list', [VehicleController::class, 'showListPage'])->name('houses.list');
        /*LIST HOUSES*/
        Route::get('/houses/list', [AdminHouseController::class, 'showListPage'])->name('houses.list');
        Route::resource('/houses', AdminHouseController::class);

        /* DOCUMENTS*/
        Route::get('/documents/list', [DocumentController::class, 'showListPageAdmin'])->name('documents.show-page');
        Route::resource('/documents', DocumentController::class);
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
            ->name('documents.download')
            ->where('document', '[0-9]+');

        /* GESTION DE CONSUMO DE AGUA / LUZ*/
        Route::get('/electricity-records/list-all', [PaymentServiceController::class, 'showPageAll'])->name('electricity-history.show-page-all');
        Route::get('/water-records/list-all', [PaymentServiceController::class, 'showPageAllWater'])->name('water.show-page-all');

        Route::get('/consumption/', [PaymentServiceController::class,'indexAll'])->name('houses.electricity-history.index-all');
        Route::post('/consumption/', [PaymentServiceController::class,'store'])->name('houses.electricity-history.store');
        Route::put('/consumption/{paymentService}', [PaymentServiceController::class,'update'])->name('houses.electricity-history.update');
        Route::delete('/consumption/{paymentService}', [PaymentServiceController::class,'destroy'])->name('houses.electricity-history.destroy');

        /* PETITIONS */
        Route::get('/petitions/list', [AdminPetitionController::class, 'showPage'])->name('petitions.show-page');;
        Route::get('/petitions', [AdminPetitionController::class, 'index'])->name('petitions.index');
        Route::get('/petitions/{petition}', [AdminPetitionController::class, 'show'])->name('petitions.show');
        Route::post('/petitions/{petition}/replies', [AdminPetitionController::class, 'addReply'])->name('petitions.replies.store');
        Route::put('/petitions/{petition}/status', [AdminPetitionController::class, 'updateStatus'])->name('petitions.status.update');

        /*PORTERO*/
        Route::get('/doorman/scanner', [DoormanController::class, 'index'])->name('doorman-scanner');
        Route::get('/doorman/check-access/{userId}', [DoormanController::class, 'checkAccess'])->name('doorman-check-access');

        /*PRESUPUESTO ANUAL*/
        Route::get('/annual-budget/list', [AnnualBudgetController::class, 'showPage'])->name('annual-budget.show-page');
        Route::get('/annual-budget', [AnnualBudgetController::class, 'index'])->name('annual-budget.index');
        Route::post('/annual-budget', [AnnualBudgetController::class, 'store'])->name('annual-budget.store');
        Route::put('/annual-budget/{annual_budget}', [AnnualBudgetController::class, 'update'])->name('annual-budget.update');
        Route::delete('/annual-budget/{annual_budget}', [AnnualBudgetController::class, 'destroy'])->name('annual-budget.destroy');

        /*TIPOS DE PRESUPUESTO*/
        Route::get('/budget-types', [BudgetTypeController::class, 'index'])->name('budget-types.index');

        /*EXPENSES*/
        Route::get('/expenses/list', [ExpenseController::class, 'showPage'])->name('expenses.show-page');
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');


        /*PROJECTS*/
        Route::get('/projects/list', [ProjectController::class, 'showListPage'])->name('projects.list');
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::patch('/projects/{project}/choose-quotation', [ProjectController::class, 'setChosenQuotation'])->name('projects.choose-quotation');

        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');


        /*QUOTATIONS*/
        Route::get('/projects/{project}/quotations', [ProjectQuotationController::class, 'index'])->name('projects.quotations.index');
        Route::post('/projects/{project}/quotations', [ProjectQuotationController::class, 'store'])->name('projects.quotations.store');
        Route::put('/projects/{project}/quotations/{quotation}', [ProjectQuotationController::class, 'update'])
            ->name('projects.quotations.update');

        Route::delete('/projects/{project}/quotations/{quotation}', [ProjectQuotationController::class, 'destroy'])
            ->name('projects.quotations.destroy');


    });

});

Route::get('/status/verify/{token}', [PublicStatusController::class, 'showStatusByToken'])
    ->name('public.user.status.by-token');
