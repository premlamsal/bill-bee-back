<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//customer
Route::get('/customers', [CustomerController::class,'index']);

Route::post('/customer/add', [CustomerController::class,'store']);

Route::post('/customer/edit', [CustomerController::class,'update']);

Route::get('/customer/{id}', [CustomerController::class,'show']);

Route::post('/customers/search', [CustomerController::class,'searchCustomers']);
//end of customer

//supplier
Route::get('/suppliers', [SupplierController::class,'index']);

Route::post('/supplier/add', [SupplierController::class,'store']);

Route::post('/supplier/edit', [SupplierController::class,'update']);

Route::get('/supplier/{id}', [SupplierController::class,'show']);


Route::post('/suppliers/search', [SupplierController::class,'searchCustomers']);
//end of customer



//product
Route::post('/product/search', [ProductController::class,'searchProduct']);

//end of product

//invoice
Route::post('/invoice/create', [InvoiceController::class,'store']);

Route::post('/invoice/edit', [InvoiceController::class,'update']);


Route::get('/invoices', [InvoiceController::class,'index']);

Route::get('/invoice/{id}', [InvoiceController::class,'show']);


//store

Route::get('/store/{id}', [StoreController::class,'show']);


//end of store

