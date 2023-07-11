<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        // Auth::user()->name,
    }

    public function index()
    {
        $store_id = Auth::user()->default_store;

        return CustomerResource::collection(Customer::where('store_id', $store_id)->orderBy('updated_at', 'desc')->paginate(8));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'address' => 'required|string|max:200',
            'phone' => 'required|unique:customers,phone|digits:10',
            'details' => 'required|string|max:400',
            'opening_balance' => 'required|numeric',

        ]);

        $store_id = Auth::user()->default_store;

        $store = Store::findOrFail($store_id);

        $customer_id_count = $store->customer_id_count;

        //explode customer id from database

        $custom_customer_id = explode('-', $customer_id_count);

        $custom_customer_id[1] = $custom_customer_id[1] + 1; //increase customer count

        //new custom_customer_id
        $custom_customer_id = implode('-', $custom_customer_id);
        $customer = new Customer();
        $customer->name = $request->input('name');
        $customer->address = $request->input('address');
        $customer->phone = $request->input('phone');
        $customer->details = $request->input('details');
        $customer->opening_balance = $request->input('opening_balance');
        $customer->custom_customer_id = $custom_customer_id;
        $customer->store_id = $store_id;
        if ($customer->save()) {
            $customerTransaction = new CustomerTransaction();
            $customerTransaction->transaction_type = 'opening_balance';
            $customerTransaction->refID = '0';
            $customerTransaction->date=date('d-m-Y');
            $customerTransaction->amount = $request->input('opening_balance');
            $customerTransaction->customer_id = $customer->id;
            $customerTransaction->store_id = $store_id;
            if ($customerTransaction->save()) {
                $store->customer_id_count = $custom_customer_id;

                if ($store->save()) {
                    return response()->json([
                        'message' => 'Customer added successfully',
                        'status' => 'success',
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Failed to update data to store ',
                        'status' => 'error',
                    ]);
                }
            } else {
                return response()->json([
                    'msg' => 'Error while adding customer transaction',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'message' => 'Customer fail to add ',
                'status' => 'error',
            ]);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'address' => 'required|string|max:200',
            'phone' => 'required|digits:10',
            'details' => 'required|string|max:400',
            'opening_balance' => 'required|numeric',

        ]);

        $store_id = Auth::user()->default_store;

        $id = $request->input('id'); //get id from edit modal
        $customer = Customer::where('id', $id)->where('store_id', $store_id)->first();
        $customer->name = $request->input('name');
        $customer->address = $request->input('address');
        $customer->phone = $request->input('phone');
        $customer->details = $request->input('details');
        $customer->opening_balance = $request->input('opening_balance');

        if ($customer->save()) {
            $customerTransaction = CustomerTransaction::where('customer_id', $customer->id)->where('transaction_type', 'opening_balance')->first();
            $customerTransaction->amount = $request->input('opening_balance');
            if ($customerTransaction->save()) {
                return response()->json([
                    'msg' => 'Customer updated successfully',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'msg' => 'Error while updating customer transaction',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'msg' => 'Error while updating customer',
                'status' => 'error',
            ]);
        }
    }

    public function destroy($id)
    {
        $store_id = Auth::user()->default_store;

        $customer = Customer::where('id', $id)->where('store_id', $store_id)->first();
        if ($customer->delete()) {
            $customerTransaction = CustomerTransaction::where('customer_id', $customer->id)->where('transaction_type', 'opening_balance')->first();
            if ($customerTransaction->delete()) {
                return response()->json([
                    'msg' => 'successfully Deleted',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'msg' => 'Error while deleting customer transaction',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'msg' => 'Error while deleting data',
                'status' => 'error',
            ]);
        }
    }

    public function show($id)
    {
        $store_id = Auth::user()->default_store;

        $customer = Customer::where('store_id', $store_id)->where('id', $id)->first();

        $invoice_amount = Invoice::where('store_id', $store_id)->where('customer_id', $id)->sum('grand_total');

        $paid_amount = CustomerPayment::where('store_id', $store_id)->where('customer_id', $id)->sum('amount');

        $balance_due = $invoice_amount - $paid_amount + ($customer->opening_balance);

        if ($customer->save()) {
            return response()->json([
                'customer' => $customer,
                'invoice_amount' => $invoice_amount,
                'paid_amount' => $paid_amount,
                'balance_due' => $balance_due,
                'status' => 'success',
                'msg' => 'Customer Fetched Successfully',

            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Customer',
                'status' => 'error',
            ]);
        }
    }

    public function showByCustomCustomerID($custom_customer_id)
    {
        $store_id = Auth::user()->default_store;

        $customer = Customer::where('custom_customer_id', $custom_customer_id)->where('store_id', $store_id)->first();

        if ($customer) {
            return response()->json([
                'message' => 'Customer fetched successfully',
                'customer' => $customer,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Error while retriving Customer',
                'status' => 'error',
            ]);
        }
    }

    public function getPayments($customer_id)
    {
        $store_id = Auth::user()->default_store;

        $CustomerPayments = CustomerPayment::where('store_id', $store_id)->where('customer_id', $customer_id)->get();

        return response()->json(['data' => $CustomerPayments, 'status' => 'success']);
    }

    public function searchCustomers(Request $request)
    {
        $store_id = Auth::user()->default_store;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return CustomerResource::collection(Customer::where('store_id', $store_id)->where('name', 'like', '%'.$searchKey.'%')->get());
        } else {
            return response()->json([
                'message' => 'Error while retriving Customer. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
