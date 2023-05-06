<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
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
        $store_id= Auth::user()->stores[0]->id;
        
        return CustomerResource::collection(Customer::orderBy('updated_at', 'desc')->paginate(8));
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


        //let store =1;
        $store_id = 1;
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

            $store->customer_id_count = $custom_customer_id;

            if ($store->save()) {

                return response()->json([
                    'msg' => 'Customer added successfully',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'msg' => 'Failed to update data to store ',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'msg' => 'Customer fail to add ',
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

        $id = $request->input('id'); //get id from edit modal
        $customer = Customer::where('id', $id)->first();
        $customer->name = $request->input('name');
        $customer->address = $request->input('address');
        $customer->phone = $request->input('phone');
        $customer->details = $request->input('details');
        $customer->opening_balance = $request->input('opening_balance');
        $customer->store_id = 1;
        if ($customer->save()) {
            return response()->json([
                'msg' => 'Customer updated successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Customer fail to update ',
                'status' => 'error',
            ]);
        }
    }
    public function destroy($id)
    {

        $customer = Customer::where('id', $id)->first();
        if ($customer->delete()) {
            return response()->json([
                'msg' => 'Customer deleted successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Customer deletion failed',
                'status' => 'error',
            ]);
        }
    }
    public function show($id)
    {

        $customer = Customer::where('id', $id)->first();
        if ($customer) {
            return response()->json([
                'msg' => 'Customer fetched successfully',
                'customer' => $customer,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Customer',
                'status' => 'error',
            ]);
        }
    }

    public function searchCustomers(Request $request)
    {

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return CustomerResource::collection(Customer::where('name', 'like', '%' . $searchKey . '%')->get());
        } else {
            return response()->json([
                'msg' => 'Error while retriving Customer. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
