<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerTransactionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierTransactionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLoginController;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


//user login
Route::post('/user-login', [LoginController::class, 'login']);


//users
Route::get('/users', [UserController::class, 'index']);

Route::post('/user/add', [UserController::class, 'store']);

Route::post('/user/edit', [UserController::class, 'update']);

Route::get('/user/{id}', [UserController::class, 'show']);

//delete single user
Route::delete('/user/{id}', [UserController::class, 'destroy']);


//roles
Route::get('/roles', [RoleController::class, 'index']);

Route::post('/role/add', [RoleController::class, 'store']);

Route::post('/role/edit', [RoleController::class, 'update']);

Route::get('/role/{id}', [RoleController::class, 'show']);

//delete single user
Route::delete('/role/{id}', [RoleController::class, 'destroy']);

//permissions
Route::get('/permissions', [PermissionController::class, 'index']);

Route::post('/permission/add', [PermissionController::class, 'store']);

Route::post('/permission/edit', [PermissionController::class, 'update']);

Route::get('/permission/{id}', [PermissionController::class, 'show']);


//delete single user
Route::delete('/permission/{id}', [PermissionController::class, 'destroy']);



//user register
Route::post('/user-register', [LoginController::class, 'register']);

//user verify
// Route::get('verify-user/{user_type}/{token}', [LoginController::class, 'verifyUser']);
Route::get('verify-user/{token}', [LoginController::class, 'verifyUser']);



// still pending to make some changes

// //reset password ...this will send link to user email
// Route::post('reset-password', [AuthController::class, 'resetPassword']);

// //new password ...this will accept the token email and new password
// Route::post('new-password', [AuthController::class, 'newPassword']);

// //check token
// Route::get('check-token/{token}', [AuthController::class, 'checkToken']);





//customer
Route::get('/customers', [CustomerController::class, 'index']);

Route::post('/customer/add', [CustomerController::class, 'store']);

Route::post('/customer/edit', [CustomerController::class, 'update']);

Route::get('/customer/{id}', [CustomerController::class, 'show']);

Route::get('/custom-customer/{id}', [CustomerController::class, 'showByCustomCustomerID']);

Route::post('/customers/search', [CustomerController::class, 'searchCustomers']);
//end of customer

//Get Customer Transactions
Route::get('customer/transactions/{id}', [CustomerTransactionController::class,'index']);

//Create new Customer payent
Route::post('customer/add-payment', [CustomerPaymentController::class,'store']);

//Create new Customer payent
Route::get('customer/payments/{customer_id}', [CustomerController::class,'getPayments']);

//get Customer payment 
Route::get('customer/payment/{payment_id}', [CustomerPaymentController::class,'show']);

//Delete customer payment
Route::delete('customer/delete-payment/{payment_id}', [CustomerPaymentController::class,'destroy']);

//update customer payment
Route::post('customer/update-payment/', [CustomerPaymentController::class,'update']);


//supplier
Route::get('/suppliers', [SupplierController::class, 'index']);

Route::post('/supplier/add', [SupplierController::class, 'store']);

Route::post('/supplier/edit', [SupplierController::class, 'update']);

Route::get('/supplier/{id}', [SupplierController::class, 'show']);


Route::get('/custom-supplier/{id}', [SupplierController::class, 'showByCustomSupplierID']);

Route::post('/suppliers/search', [SupplierController::class, 'searchSuppliers']);
//end of customer



//Get Supplier Transactions
Route::get('supplier/transactions/{id}', [SupplierTransactionController::class,'index']);

//Create new Supplier payent
Route::post('supplier/add-payment', [SupplierPaymentController::class,'store']);

//Create new Supplier payent
Route::get('supplier/payments/{supplier_id}', [SupplierController::class,'getPayments']);

//get Supplier payment 
Route::get('supplier/payment/{payment_id}', [SupplierPaymentController::class,'show']);

//Delete supplier payment
Route::delete('supplier/delete-payment/{payment_id}', [SupplierPaymentController::class,'destroy']);

//Delete supplier payment
Route::post('supplier/update-payment/', [SupplierPaymentController::class,'update']);



//product
Route::get('/products', [ProductController::class, 'index']);

Route::post('/product/add', [ProductController::class, 'store']);

Route::post('/product/edit', [ProductController::class, 'update']);

Route::get('/product/{id}', [ProductController::class, 'show']);


Route::post('/product/search', [ProductController::class, 'searchProduct']);

//end of product

//invoice
Route::post('/invoice/create', [InvoiceController::class, 'store']);

Route::post('/invoice/edit', [InvoiceController::class, 'update']);


Route::get('/invoices', [InvoiceController::class, 'index']);

Route::get('/invoice/{id}', [InvoiceController::class, 'show']);


//store

Route::get('/store/{id}', [StoreController::class, 'show']);


Route::get('/user-store', [StoreController::class, 'getUserStore']);


//end of store

//purchase
Route::post('/purchase/create', [PurchaseController::class, 'store']);

Route::post('/purchase/edit', [PurchaseController::class, 'update']);


Route::get('/purchases', [PurchaseController::class, 'index']);

Route::get('/purchase/{id}', [PurchaseController::class, 'show']);




//category

Route::get('/categories', [ProductCategoryController::class, 'index']);


//end of category


//unit

Route::get('/units', [UnitController::class, 'index']);


//end of unit


//store
Route::post('/create-store',[StoreController::class,'store']);



//end of store




//check user has store or not
Route::get('store/check', [CheckController::class,'checkUserForStore']);

//get all store that are assigned to current user
Route::get('user-stores', [CheckController::class,'stores']);

//save one default store from all store of current user
Route::post('save-store', [CheckController::class,'saveUserDefaultStore']);



//check user has permissions or not
Route::get('permissions/check', [CheckController::class,'checkPermissions']);



//check user has stores or not
Route::get('stores/check', [CheckController::class,'hasStore']);




//category
Route::get('/categories', [ProductCategoryController::class, 'index']);

Route::post('/category/add', [ProductCategoryController::class, 'store']);

Route::post('/category/edit', [ProductCategoryController::class, 'update']);

Route::get('/category/{id}', [ProductCategoryController::class, 'show']);

Route::post('/categories/search', [ProductCategoryController::class, 'searchCategory']);
//end of category



//unit
Route::get('/units', [UnitController::class, 'index']);

Route::post('/unit/add', [UnitController::class, 'store']);

Route::post('/unit/edit', [UnitController::class, 'update']);

Route::get('/unit/{id}', [UnitController::class, 'show']);

Route::post('/units/search', [UnitController::class, 'searchUnits']);
//end of unit


//accounts

//List account
Route::get('accounts', [AccountController::class,'index']);

//Create new account
Route::post('account/add',[AccountController::class,'store']);

//List single account
Route::get('account/{id}',[AccountController::class,'show']);

Route::get('custom-account/{id}',[AccountController::class,'showByCustomAccountID']);

//Update account
Route::post('account/edit', [AccountController::class,'update']);

//Delete account
Route::delete('account/{id}', [AccountController::class,'destroy']);

//search accounts
Route::post('accounts/search', [AccountController::class,'searchAccounts']);

//Get Account Transactions
Route::get('account/transactions/{id}', [AccountController::class,'getAccountTransactions']);


//get all transactions
Route::get('transactions', [TransactionController::class,'index']);

Route::post('transaction/add', [TransactionController::class,'store']);

Route::get('transaction/{id}', [TransactionController::class,'show']);

//Update transaction
Route::post('transaction/edit', [TransactionController::class,'update']);

//Delete transaction
Route::delete('transaction/delete/{id}', [TransactionController::class,'destroy']);