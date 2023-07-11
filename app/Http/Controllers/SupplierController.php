<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupplierResource;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:api');
    }
    public function index()
    {
        $store_id = Auth::user()->default_store;
        return SupplierResource::collection(Supplier::where('store_id', $store_id)->orderBy('updated_at', 'desc')->paginate(8));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'address' => 'required|string|max:200',
            'phone' => 'required|unique:suppliers,phone|digits:10',
            'details' => 'required|string|max:400',
            'opening_balance' => 'required|numeric',

        ]);


        //let store =1;
        $store_id = Auth::user()->default_store;
        $store = Store::findOrFail($store_id);

        $supplier_id_count = $store->supplier_id_count;

        //explode supplier id from database

        $custom_supplier_id = explode('-', $supplier_id_count);

        $custom_supplier_id[1] = $custom_supplier_id[1] + 1; //increase supplier count

        //new custom_supplier_id
        $custom_supplier_id = implode('-', $custom_supplier_id);
        $supplier = new Supplier();
        $supplier->name = $request->input('name');
        $supplier->address = $request->input('address');
        $supplier->phone = $request->input('phone');
        $supplier->details = $request->input('details');
        $supplier->opening_balance = $request->input('opening_balance');
        $supplier->custom_supplier_id = $custom_supplier_id;
        $supplier->store_id = $store_id;
        if ($supplier->save()) {



            $SupplierTransaction = new SupplierTransaction();
            $SupplierTransaction->transaction_type = 'opening_balance';
            $SupplierTransaction->refID = '0';
            $SupplierTransaction->amount = $request->input('opening_balance');
            $SupplierTransaction->supplier_id = $supplier->id;
            $SupplierTransaction->store_id = $store_id;
            if ($SupplierTransaction->save()) {

                $store->supplier_id_count = $custom_supplier_id;

                if ($store->save()) {

                    return response()->json([
                        'message' => 'Supplier added successfully',
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
                    'msg' => 'Error while adding Supplier transaction',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'message' => 'Supplier fail to add ',
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

        $store_id = Auth::user()->default_store;


        $supplier = Supplier::where('store_id', $store_id)->where('id', $id)->first();

        $supplier->name = $request->input('name');
        $supplier->address = $request->input('address');
        $supplier->phone = $request->input('phone');
        $supplier->details = $request->input('details');
        $supplier->opening_balance = $request->input('opening_balance');
        if ($supplier->save()) {

            $SupplierTransaction = SupplierTransaction::where('supplier_id',$supplier->id)->where('transaction_type','opening_balance')->first();
            $SupplierTransaction->amount = $request->input('opening_balance');
            if ($SupplierTransaction->save()) {
                return response()->json([
                    'msg' => 'Supplier updated successfully',
                    'status' => 'success',
                ]);

            }else{
                return response()->json([
                    'msg' => 'Error while updating Supplier transaction',
                    'status' => 'error',
                ]); 
            }
        } else {

            return response()->json([

                'msg'    => 'Error while updating supplier',
                'status' => 'error',
            ]);
        }
    }
    public function destroy($id)
    {

        $store_id = Auth::user()->default_store;

        $supplier = Supplier::where('store_id', $store_id)->where('id', $id)->first();
        if ($supplier->delete()) {
            $SupplierTransaction = SupplierTransaction::where('customer_id', $supplier->id)->where('transaction_type', 'opening_balance')->first();
            if ($SupplierTransaction->delete()) {
                return response()->json([
                    'msg' => 'successfully Deleted',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'msg' => 'Error while deleting Supplier transaction',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'msg'    => 'Error while deleting data',
                'status' => 'error',
            ]);
        }
    }
    public function show($id)
    {

        $store_id = Auth::user()->default_store;

        $supplier = Supplier::where('id', $id)->where('store_id', $store_id)->first();

        $purchase_amount=Purchase::where('store_id',$store_id)->where('supplier_id',$id)->sum('grand_total');
        $paid_amount=SupplierPayment::where('store_id',$store_id)->where('supplier_id',$id)->sum('amount');
        $balance_due=floatval($purchase_amount)-floatval($paid_amount)-floatval($supplier->opening_balance);

        if ($supplier->save()) {
            return response()->json([
                'supplier' => $supplier,
                'purchase_amount'=>$purchase_amount,
                'paid_amount'=>$paid_amount,
                'balance_due'=>$balance_due,
                'status' => 'success',
                'message' => 'Supplier fetched successfully',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Supplier',
                'status' => 'error',
            ]);
        }
    }

    public function getPayments($supplier_id)
    {


        $store_id = Auth::user()->default_store;


        $SupplierPayments = SupplierPayment::where('store_id', $store_id)->where('supplier_id', $supplier_id)->get();

        return response()->json(['data' => $SupplierPayments, 'status' => 'success']);
    }


    public function searchSuppliers(Request $request)
    {
        $store_id = Auth::user()->default_store;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return SupplierResource::collection(Supplier::where('store_id', $store_id)->where('name', 'like', '%' . $searchKey . '%')->get());
        } else {
            return response()->json([
                'message' => 'Error while retriving Supplier. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
