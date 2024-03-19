<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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


Route::post('account/generate_2fa_code', [ProfileController::class, 'generate2FACode']);
Route::post('2fa', [ProfileController::class, 'checkOTP']);

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [UserController::class, 'current']);
    Route::post('account/profile', [ProfileController::class, 'update']);
    Route::patch('account/password', [PasswordController::class, 'update']);;
});

Route::group(['middleware' => 'guest:api'], function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::group([
        'prefix' => 'user',
        'middleware' => 'auth:api',
        'controller' => UserController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->post('/create', 'create');
        $router->post('/update', 'update');
        $router->get('/get_detail/{id}', 'getDetail');
        $router->delete('/delete/{id}', 'delete');
    });

Route::group([
        'prefix' => 'supplier',
        'middleware' => 'auth:api',
        'controller' => SupplierController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->post('/save', 'save');
        $router->get('/get_detail/{id}', 'getDetail');
        $router->get('/report/{id}', 'report');
        $router->delete('/delete/{id}', 'delete');
    });

Route::group([
        'prefix' => 'company',
        'middleware' => 'auth:api',
        'controller' => CompanyController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->post('/save', 'save')->middleware('role:admin');
        $router->get('/get_detail/{id}', 'getDetail')->middleware('role:admin');
        $router->delete('/delete/{id}', 'delete')->middleware('role:admin');
    });

Route::group([
        'prefix' => 'store',
        'middleware' => 'auth:api',
        'controller' => StoreController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->post('/save', 'save')->middleware('role:admin');
        $router->get('/get_detail/{id}', 'getDetail');
        $router->delete('/delete/{id}', 'delete')->middleware('role:admin');
    });

Route::group([
        'prefix' => 'category',
        'middleware' => 'auth:api',
        'controller' => CategoryController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->post('/save', 'save');
        $router->get('/get_detail/{id}', 'getDetail');
        $router->delete('/delete/{id}', 'delete');
    });

Route::group([
        'prefix' => 'purchase',
        'middleware' => 'auth:api',
        'controller' => PurchaseController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->post('/create', 'create');
        $router->post('/update', 'update');
        $router->get('/get_detail/{id}', 'getDetail');
        $router->get('/report/{id}', 'report');
        $router->get('/get_next_reference_no', 'getNextReferenceNo');
        $router->delete('/delete/{id}', 'delete');
    });

Route::group([
        'prefix' => 'purchase_order',
        'middleware' => 'auth:api',
        'controller' => PurchaseOrderController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->post('/create', 'create');
        $router->post('/update', 'update');
        $router->post('/receive', 'receive');
        $router->get('/get_detail/{id}', 'getDetail');
        $router->get('/get_next_reference_no', 'getNextReferenceNo');
        $router->delete('/delete/{id}', 'delete')->middleware('role:secretary:false');
    });

Route::group([
        'prefix' => 'report',
        'middleware' => 'auth:api',
        'controller' => ReportController::class
    ], function ($router) {
        $router->post('/purchases_report', 'purchasesReport');
        $router->post('/suppliers_report', 'suppliersReport');
    });

Route::group([
        'prefix' => 'notification',
        'middleware' => 'auth:api',
        'controller' => NotificationController::class
    ], function ($router) {
        $router->get('/search', 'search');
        $router->get('/get_unread_notification_count', 'getUnreadNotificationCount');
        $router->post('/mark_as_read', 'markAsRead');
        $router->delete('/delete/{id}', 'delete');
        $router->delete('/delete_all', 'deleteAll');
    });

Route::group([
        'middleware' => 'auth:api',
        'controller' => HomeController::class
    ], function ($router) {
        $router->get('/get_dashboard_data', 'getDashboardData');
        $router->post('/get_category_chart_data', 'getCategoryChartData');
        $router->get('/get_suppliers', 'getSuppliers');
        $router->get('/get_categories', 'getCategories');
        $router->delete('/image/delete/{id}', 'deleteImage');
    });