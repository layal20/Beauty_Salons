<?php

use App\Http\Controllers\Api\AdminsController;
use App\Http\Controllers\Api\AppointmentsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartsController;
use App\Http\Controllers\Api\CustomersController;
use App\Http\Controllers\Api\EmployeesController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\SalonsController;
use App\Http\Controllers\Api\ServicesController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('salon/store', [SalonsController::class, 'store'])->name('salon');
Route::get('salon/show/{id}', [SalonsController::class, 'show'])->name('salon_details');
Route::post('salon/update/{id}', [SalonsController::class, 'update']);
Route::delete('salon/delete/{id}', [SalonsController::class, 'destroy']);

Route::get('products', [ProductsController::class, 'index']);
Route::get('product/show/{id}', [ProductsController::class, 'show'])->name('product_details');
Route::post('product/store', [ProductsController::class, 'store']);
Route::post('product/update/{id}', [ProductsController::class, 'update']);
Route::delete('product/delete/{id}', [ProductsController::class, 'destroy']);

Route::get('services', [ServicesController::class, 'index']);
Route::post('service/store', [ServicesController::class, 'store']);
Route::get('service/show/{id}', [ServicesController::class, 'show'])->name('service_details');
Route::post('service/update/{id}', [ServicesController::class, 'update']);
Route::delete('service/delete/{id}', [ServicesController::class, 'destroy']);



Route::get('salons', [HomeController::class, 'index']);


Route::get('employees', [EmployeesController::class, 'index']);
Route::get('employee/show/{id}', [EmployeesController::class, 'show'])->name('employee_details');
Route::post('employee/store', [EmployeesController::class, 'store']);
Route::post('employee/update/{id}', [EmployeesController::class, 'update']);
Route::delete('employee/delete/{id}', [EmployeesController::class, 'destroy']);

Route::get('admins', [AdminsController::class, 'index']);
Route::get('admin/show/{id}', [AdminsController::class, 'show'])->name('admin_details');
Route::post('admin/store', [AdminsController::class, 'store']);
Route::post('admin/update/{id}', [AdminsController::class, 'update']);
Route::delete('admin/delete/{id}', [AdminsController::class, 'destroy']);

Route::get('findService/{name}', [CustomersController::class, 'searchAboutService'])->name('service_details');;
Route::get('findProduct/{name}', [CustomersController::class, 'searchAboutProduct'])->name('search_product');
Route::get('findSalon/{name}', [CustomersController::class, 'searchAboutSalon'])->name('search_salon');
Route::get('findAdmin/{name}', [AdminsController::class, 'searchAboutAdmin'])->name('admin_details');
Route::get('findEmployee/{name}', [CustomersController::class, 'searchAboutEmployee'])->name('employee_details');

Route::post('loginSuperAdmin', [AuthController::class, 'loginSuperAdmin']);
Route::post('registerAdmin', [AuthController::class, 'registerAdmin']);
Route::post('loginAdmin', [AuthController::class, 'loginAdmin']);


Route::post('customerRegister', [AuthController::class, 'CustomerRegister']);
Route::post('customerLogin', [AuthController::class, 'CustomerLogin']);
Route::post('logout' , [AuthController::class , 'logout']);

Route::post('addItem/{productId}' , [CartsController::class , 'addProductToCart']);
Route::post('updateItemQuantity/{cartId}/{productId}', [CartsController::class , 'updateCartItem']);
Route::get('showCart/{cartId}' , [CartsController::class , 'showCart']);
Route::get('allCarts' , [CartsController::class , 'allCarts']);
Route::delete('deleteCartItem/{cartId}/{productId}' , [CartsController::class , 'removeProductFromCart']);
Route::delete('deleteCart/{cartId}' , [CartsController::class , 'deleteCart']);
Route::get('viewAllUserBooking/{customerId}', [CartsController::class , 'getCustomerBook']);


Route::post('addAppointment/{serviceId}' , [AppointmentsController::class , 'addAnAppointment']);
Route::delete('cancelAppointment/{appointmentId}' , [AppointmentsController::class , 'cancelAppointment']);
Route::post('updateAppointment/{appointmentId}' , [AppointmentsController::class , 'updateAppointmentDetails']);
Route::get('appointments' , [AppointmentsController::class , 'viewAllAppointments']);
Route::get('showAppointment/{appointmentId}', [AppointmentsController::class, 'viewAppointment']);
Route::get('viewAllUserAppointments/{customerId}', [AppointmentsController::class, 'getCustomerAppointments']);
