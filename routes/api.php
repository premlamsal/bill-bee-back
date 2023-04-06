<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
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

Route::post('/customers/search', [CustomerController::class,'searchCustomers']);
//end of customer

//supplier
Route::get('/suppliers', [SupplierController::class,'index']);

Route::post('/suppliers/search', [SupplierController::class,'searchCustomers']);
//end of customer



//product
Route::post('/product/search', [ProductController::class,'searchProduct']);

//end of product

//invoice
Route::post('/invoice/create', [InvoiceController::class,'store']);
Route::get('/invoices', [InvoiceController::class,'index']);


