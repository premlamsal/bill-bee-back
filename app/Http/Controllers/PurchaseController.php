<?php

namespace App\Http\Controllers;

use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Stock;
use App\Models\Store;
use App\Models\SupplierTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

        // Auth::user()->name,
    }

    public function index()
    {
        $store_id = Auth::user()->default_store;

        return PurchaseResource::collection(Purchase::where('store_id', $store_id)->with('purchaseDetail')->orderBy('updated_at', 'desc')->paginate(8));
    }

    public function store(Request $request)
    {
        $purchase_status_save = false;

        // $this->authorize('hasPermission', 'add_purchase');

        $store_id = Auth::user()->default_store;

        //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.supplier_name' => 'required | string| max:200',
            'info.supplier_id' => 'required',
            'info.due_date' => 'required | date',
            'info.purchase_date' => 'required | date',

            'info.purchase_reference_id' => 'required | string| max:200',

            'info.discount' => 'required | numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);

        $store = Store::findOrFail($store_id);
        $store_tax_percentage = $store->tax_percentage;

        $store_tax = $store_tax_percentage / 100;

        //old invoice id
        $purchase_id_count = $store->purchase_id_count;

        //explode invoice id from database

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

        $data['sub_total'] = $items->sum('line_total');

        $data['tax_amount'] = $data['sub_total'] * $store_tax;

        $data['grand_total'] = $data['sub_total'] + $data['tax_amount'] - $data['discount'];

        $data['store_id'] = $store_id;

        $data['purchase_reference_id'] = $data['purchase_reference_id'];

        $data['custom_purchase_id'] = $new_count_purchase_id;

        $purchase = Purchase::create($data);

        $purchase->purchaseDetail()->saveMany($items);

        //for inserting in stock and altering if already has one initialized stock and previous stock
        $items = collect($request->items);

        $countItems = count($items);

        $timeStamp = now();

        $jsonResponse = [];

        for ($i = 0; $i < $countItems; $i++) {
            $p_id = $items[$i]['product_id'];

            $stock = Stock::where('store_id', $store_id)->where('product_id', $p_id);

            //retirving current product-> stock quantity
            $in_stock_quantity = $stock->value('quantity');

            //get stock id
            $stock_id = $stock->value('id');

            $stock_price_old = $stock->value('price');

            //adding current stock with new purchased product quantity
            $new_stock_quantity = $in_stock_quantity + $items[$i]['quantity'];

            //found product on stock
            if ($stock_id != 0) {
                //found product that have same price on stock so updating the quanity for the product but same price
                if ($stock_price_old == $items[$i]['price']) {
                    $stock = Stock::findOrFail($stock_id);

                    $stock->quantity = $new_stock_quantity;

                    $stock->updated_at = $timeStamp;

                    if ($stock->save()) {
                        //set current purchase_id_count to store table
                        $store->purchase_id_count = $new_count_purchase_id;
                        if ($store->save()) {
                            $purchase_status_save = true;
                        } else {
                            $jsonResponse = ['message' => 'Failed updating the Data to the store.', 'status' => 'error3'];
                        }
                    } else {
                        $jsonResponse = ['message' => 'Failed Saving the Data to the Stock.', 'status' => 'error3'];
                    }
                } else { //the price is diff so we have to add new stock for the new price of that product
                    $stock = new Stock();

                    $stock->quantity = $new_stock_quantity;

                    $stock->updated_at = $timeStamp;

                    $stock->product_id = $p_id;

                    $stock->quantity = $new_stock_quantity;

                    $stock->price = $items[$i]['price'];

                    $stock->unit_id = $items[$i]['unit_id'];

                    $stock->created_at = $timeStamp;

                    $stock->updated_at = $timeStamp;

                    $stock->store_id = $store_id;

                    if ($stock->save()) {
                        //set current purchase_id_count to store table
                        $store->purchase_id_count = $new_count_purchase_id;
                        if ($store->save()) {
                            $purchase_status_save = true;
                        } else {
                            $jsonResponse = ['message' => 'Failed updating the Data to the store.', 'status' => 'error3'];
                        }
                    } else {
                        $jsonResponse = ['message' => 'Failed Saving the Data to the Stock.', 'status' => 'error3'];
                    }
                }
            } else {
                //couldn't find the product on stock
            }
        }
        if ($purchase_status_save) {
            $SupplierTransaction = new SupplierTransaction();
            $SupplierTransaction->transaction_type = 'purchase';
            $SupplierTransaction->refId = $purchase->id;
            $SupplierTransaction->amount = $data['grand_total'];
            $SupplierTransaction->supplier_id = $data['supplier_id'];
            $SupplierTransaction->store_id = $data['store_id'];
            $SupplierTransaction->date = $data['purchase_date'];
            if ($SupplierTransaction->save()) {
                $jsonResponse = ['message' => 'Successfully created purchase', 'status' => 'success'];
            } else {
                $jsonResponse = ['message' => 'Error adding purchase to supplier transaction.', 'status' => 'error'];
            }
        }

        return response()->json($jsonResponse);
    }

    public function update(Request $request)
    {
        // $this->authorize('hasPermission', 'edit_purchase');

        $store_id = Auth::user()->default_store;

        // //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.supplier_name' => 'required | string| max:200',
            'info.supplier_id' => 'required',
            'info.due_date' => 'required | date',
            'info.purchase_date' => 'required | date',

            'info.discount' => 'required | numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);
        $id = $request->id; //we will get purchase id here

        $purchase = Purchase::where('id', $id)->where('store_id', $store_id)->first();

        $items = collect($request->items)->transform(function ($item) {
            $item['line_total'] = $item['quantity'] * $item['price'];

            return new PurchaseDetail($item);
        });

        if ($items->isEmpty()) {
            return response()
                ->json([
                    'items_empty' => ['One or more Item is required.'],
                ], 422);
        }

        $store = Store::findOrFail($store_id);

        $store_tax_percentage = $store->tax_percentage;

        $store_tax = $store_tax_percentage / 100;

        $data = $request->info;

        $data['sub_total'] = $items->sum('line_total');
        $data['tax_amount'] = $data['sub_total'] * $store_tax;
        $data['grand_total'] = $data['sub_total'] + $data['tax_amount'] - $data['discount'];
        $data['store_id'] = $store_id;

        //first get old items
        // Get Purchase
        $Purchase = Purchase::where('id', $id)->where('store_id', $store_id)->first();

        //get purchase details
        $purchaseDetail = PurchaseDetail::where('purchase_id', $id)->get();

        $countItems = count($purchaseDetail);

        $check_save_stock = false;

        // $timeStamp=now();
        if ($countItems != 0) {
            for ($i = 0; $i < $countItems; $i++) {
                //get product id from each purchase details
                $p_id = $purchaseDetail[$i]['product_id'];

                $old_purchase_detail_qty = $purchaseDetail[$i]['quantity'];

                //finding stock to decrease the quantity of this purchase
                $stock = Stock::where('product_id', $p_id)->where('store_id', $store_id);

                $stock_id = $stock->value('id');

                $stock_qty = $stock->value('quantity');

                $old_stock_qty = $stock_qty - $old_purchase_detail_qty;

                $stock = Stock::where('id', $stock_id)->where('store_id', $store_id)->first();

                $stock->quantity = $old_stock_qty + $items[$i]['quantity'];

                if ($stock->save()) {
                    $check_save_stock = true;
                } else {
                    $check_save_stock = false;
                }
            }
            if ($check_save_stock) {
                $purchase->update($data);

                PurchaseDetail::where('purchase_id', $purchase->id)->delete();

                $purchase->purchaseDetail()->saveMany($items);

                $SupplierTransaction = SupplierTransaction::where('refID', $purchase->id)->where('store_id', $store_id)->first();
                // $SupplierTransaction->transaction_type = "sales";
                // $SupplierTransaction->refId = $purchase->id;
                $SupplierTransaction->amount = $data['grand_total'];
                $SupplierTransaction->supplier_id = $data['supplier_id'];
                // $SupplierTransaction->store_id = $data['store_id'];
                $SupplierTransaction->date = $data['purchase_date'];
                if ($SupplierTransaction->save()) {
                    return response()->json(['message' => 'You have successfully updated the Purchase.', 'status' => 'success']);
                } else {
                    return response()->json(['message' => 'Error adding purchase to supplier transaction.', 'status' => 'success'], 500);
                }
            } else {
                //saving stock fails
                return response()->json(['message' => 'Initial update to stock failed.', 'status' => 'error'], 500);
            }

        // check stock save status and do following
        } else {
            return response()->json([
                'message' => 'Update Failed. There is no items in this purchase',
                'status' => 'error',
            ], 500);
        }
    }

    public function show($id)
    {
        $store_id = Auth::user()->default_store;

        $purchase = Purchase::where('store_id', $store_id)->where('custom_purchase_id', $id)->with('purchaseDetail.product.unit')->with('supplier')->first();

        if ($purchase) {
            return response()->json([
                'message' => 'Purchases fetched successfully',
                'purchase' => $purchase,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Error while retriving Purchase',
                'status' => 'danger',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $store_id = Auth::user()->default_store;

        // Get Purchase
        $Purchase = Purchase::where('id', $id)->where('store_id', $store_id)->first();

        //get purchase details
        $purchaseDetail = PurchaseDetail::where('purchase_id', $id)->get();

        $countItems = count($purchaseDetail);

        // $timeStamp=now();

        for ($i = 0; $i < $countItems; $i++) {
            //get product id from each purchase details
            $p_id = $purchaseDetail[$i]['product_id'];

            $p_qty = $purchaseDetail[$i]['quantity'];

            //finding stock to decrease the quantity of this purchase
            $stock = Stock::where('store_id', $store_id)->where('product_id', $p_id);

            $stock_id = $stock->value('id');

            $stock_qty = $stock->value('quantity');

            $stock = Stock::findOrFail($stock_id);

            if ($stock_qty >= $p_qty) {
                $stock->quantity = $stock_qty - $p_qty;
            }
            if ($stock->save()) {
                if ($Purchase->delete()) {
                    return response()->json([
                        'message' => 'successfully Deleted',
                        'status' => 'success',
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Delete Failed',
                        'status' => 'error',
                    ]);
                }
            }
        }
    }

    public function searchPurchases(Request $request)
    {
        // $this->authorize('hasPermission', 'search_purchase');

        $store_id = Auth::user()->default_store;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return PurchaseResource::collection(Purchase::where('store_id', $store_id)->where('customer_name', 'like', '%'.$searchKey.'%')->get());
        } else {
            return response()->json([
                'message' => 'Error while retriving Purchases. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
