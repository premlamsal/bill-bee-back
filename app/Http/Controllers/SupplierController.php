<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupplierResource;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return supplierResource::collection(supplier::orderBy('updated_at', 'desc')->paginate(8));
    }
    public function store(Request $request)
    {

        //let store =1;
        $store_id = 1;

        $store = Store::findOrFail($store_id);

        $supplier_id_count = $store->supplier_id_count;

        //explode supplier id from database

        $custom_supplier_id = explode('-', $supplier_id_count);

        $custom_supplier_id[1] = $custom_supplier_id[1] + 1; //increase supplier count

        //new custom_supplier_id
        $custom_supplier_id = implode('-', $custom_supplier_id);


        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'address' => 'required|string|max:200',
            'phone' => 'required|unique:suppliers,phone|digits:10',
            'details' => 'required|string|max:400',
            'opening_balance' => 'required|numeric',

        ]);
        $supplier = new supplier();
        $supplier->name = $request->input('name');
        $supplier->address = $request->input('address');
        $supplier->phone = $request->input('phone');
        $supplier->details = $request->input('details');
        $supplier->opening_balance = $request->input('opening_balance');
        $supplier->custom_supplier_id = $custom_supplier_id;
        $supplier->store_id = $store_id;
        if ($supplier->save()) {
            $store->supplier_id_count = $custom_supplier_id;

            if ($store->save()) {
                return response()->json([
                    'msg' => 'supplier added successfully',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'msg' => 'failed to update store data ',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'msg' => 'supplier fail to add ',
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
        $supplier = supplier::where('id', $id)->first();
        $supplier->name = $request->input('name');
        $supplier->address = $request->input('address');
        $supplier->phone = $request->input('phone');
        $supplier->details = $request->input('details');
        $supplier->opening_balance = $request->input('opening_balance');
        // $supplier->store_id = 1;
        if ($supplier->save()) {
            return response()->json([
                'msg' => 'supplier updated successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'supplier fail to update ',
                'status' => 'error',
            ]);
        }
    }
    public function destroy($id)
    {

        $supplier = supplier::where('id', $id)->first();
        if ($supplier->delete()) {
            return response()->json([
                'msg' => 'supplier deleted successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'supplier deletion failed',
                'status' => 'error',
            ]);
        }
    }
    public function show($id)
    {

        $supplier = Supplier::where('id', $id)->first();
        if ($supplier) {
            return response()->json([
                'msg' => 'supplier fetched successfully',
                'supplier' => $supplier,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving supplier',
                'status' => 'error',
            ]);
        }
    }

    public function searchsuppliers(Request $request)
    {

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return supplierResource::collection(supplier::where('name', 'like', '%' . $searchKey . '%')->get());
        } else {
            return response()->json([
                'msg' => 'Error while retriving supplier. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
