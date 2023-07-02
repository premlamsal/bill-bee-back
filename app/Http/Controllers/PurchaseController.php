<?php

namespace App\Http\Controllers;

use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index()
    {

        return PurchaseResource::collection(Purchase::with('purchaseDetail')->orderBy('updated_at', 'desc')->paginate(8));
    }

    public function store(Request $request)
    {

        // //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.supplier_name' => 'required | string| max:200',
            'info.supplier_id' => 'required',
            'info.due_date' => 'required | date',
            'info.purchase_date' => 'required | date',
            'info.supplier_id' => 'required',
            'info.discount' => 'numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);

        $purchase_status_save = false;

        $store_id = Auth::user()->default_store;

        $store = Store::findOrFail($store_id);

        $store_tax_percentage = $store->tax_percentage;

        $store_tax = $store_tax_percentage / 100;

        //old purchase id
        $purchase_id_count = $store->purchase_id_count;

        //explode purchase id from database

        $custom_purchase_id = explode('-', $purchase_id_count);

        $custom_purchase_id[1] = $custom_purchase_id[1] + 1; //increase purchase

        //new custom_purchase_id
        $new_count_purchase_id = implode('-', $custom_purchase_id);



        //collecting data
        $items = collect($request->items)->transform(function ($item) {
            $item['line_total'] = $item['quantity'] * $item['price'];
            return new PurchaseDetail($item);
        });

        if ($items->isEmpty()) {
            return response()
                ->json([
                    'items_empty' => 'One or more Item is required.',
                ], 422);
        }



        $data = $request->info;
        if (array_key_exists('discount', $data)) {
            $data['grand_total'] = $data['sub_total'] + $data['tax_amount'] - $data['discount'];
        }

        $data['sub_total'] = $items->sum('line_total');

        $data['discount'] = 0;

        $data['status'] = 0;

        $data['tax_amount'] = $data['sub_total'] * $store_tax;

        $data['grand_total'] = $data['sub_total'] + $data['tax_amount'];

        $data['store_id'] = $store_id;

        $data['custom_purchase_id'] = $new_count_purchase_id;

        //transaction started

        $purchase = Purchase::create($data);

        $purchase->purchaseDetail()->saveMany($items);

        //set current purchase_id_count to store table
        $store->purchase_id_count = $new_count_purchase_id;

        if ($store->save()) {
            $purchase_status_save = true;
        } else {

            $jsonResponse = ['msg' => 'Failed updating the Data to the store.', 'status' => 'error'];
        }
        if ($purchase_status_save) {

            $jsonResponse = ['msg' => 'Successfully created purchase & update store data ', 'status' => 'success'];
        }
        return response()->json($jsonResponse);
    }
    public function update(Request $request)
    {
        //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.supplier_name' => 'required | string| max:200',
            'info.supplier_id' => 'required',
            'info.due_date' => 'required | date',
            'info.purchase_date' => 'required | date',
            'info.supplier_id' => 'required',
            'info.discount' => 'required | numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);


        $store_id = Auth::user()->default_store;

        $id = $request->id; //we will get purchase id here

        $purchase = Purchase::where('custom_purchase_id', $id)->where('store_id', $store_id)->first();

        $items = collect($request->items)->transform(function ($item) {
            $item['line_total'] = $item['quantity'] * $item['price'];
            return new PurchaseDetail($item);
        });
        $store = Store::findOrFail($store_id);
        $store_tax_percentage = $store->tax_percentage;

        $store_tax = $store_tax_percentage / 100;

        if ($items->isEmpty()) {
            return response()
                ->json([
                    'items_empty' => ['One or more Item is required.'],
                ], 422);
        }

        $data = $request->info;

        $data['sub_total'] = $items->sum('line_total');
        $data['tax_amount'] = $data['sub_total'] * $store_tax;
        $data['grand_total'] = $data['sub_total'] + $data['tax_amount'] - $data['discount'];
        $data['store_id'] = $store_id;


        $purchase->update($data);

        PurchaseDetail::where('purchase_id', $purchase->id)->delete();

        $purchase->PurchaseDetail()->saveMany($items);


        if ($items->isEmpty()) {
            return response()
                ->json([
                    'items_empty' => ['One or more Item is required.'],
                ], 422);
        }


        if ($purchase) {
            return response()->json([
                'msg' => 'Purchases updated successfully',
                'purchase' => $purchase,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while updating Purchase',
                'status' => 'danger',
            ], 500);
        }
    }
    public function show($id)
    {

        $store_id = Auth::user()->default_store;
        
        $purchase = Purchase::where('store_id',$store_id)->where('custom_purchase_id', $id)->with('purchaseDetail.product.unit')->with('supplier')->first();
        
        if ($purchase) {
            return response()->json([
                'msg' => 'Purchases fetched successfully',
                'purchase' => $purchase,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Purchase',
                'status' => 'danger',
            ], 500);
        }
    }
}
