<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Stock;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:api');

        // Auth::user()->name,

    }
    public function index()
    {
        $store_id = Auth::user()->default_store;

        return InvoiceResource::collection(Invoice::where('store_id',$store_id)->with('invoiceDetail')->orderBy('updated_at', 'desc')->paginate(8));
    }

    public function store(Request $request)
    {
        $invoice_status_save = false;

        // $this->authorize('hasPermission', 'add_invoice');

      
        $store_id = Auth::user()->default_store;

        // //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.customer_name' => 'required | string| max:200',
            'info.customer_id' => 'required',
            'info.due_date' => 'required | date',
            'info.invoice_date' => 'required | date',
            'info.customer_id' => 'required',
            'info.discount' => 'required | numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);

        // $settings=Setting::findOrFail(1);

        $store = Store::findOrFail($store_id);

        $store_tax_percentage = $store->tax_percentage;

        $store_tax = $store_tax_percentage / 100;

        //old invoice id
        $invoice_id_count = $store->invoice_id_count;

        //explode invoice id from database

        $custom_invoice_id = explode('-', $invoice_id_count);

        $custom_invoice_id[1] = $custom_invoice_id[1] + 1; //increase invoice

        //new custom_invoice_id
        $new_count_invoice_id = implode('-', $custom_invoice_id);

        //collecting data
        $items = collect($request->items)->transform(function ($item) {
            $item['line_total'] = $item['quantity'] * $item['price'];
            return new InvoiceDetail($item);
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
        $data['custom_invoice_id'] = $new_count_invoice_id;

        //transaction started

        $invoice = Invoice::create($data);

        $invoice->invoiceDetail()->saveMany($items);

        //for inserting in stock and altering if already has one initialized stock and previous stock
        $items = collect($request->items);

        $countItems = count($items);

        $timeStamp = now();

        $jsonResponse = array();

        for ($i = 0; $i < $countItems; $i++) {

            $p_id = $items[$i]['product_id'];

            $stock_id = $items[$i]['stock_id'];

            $stock = Stock::where('id', $stock_id)->where('product_id', $p_id)->where('store_id', $store_id);

            //retirving current product-> stock quantity
            $in_stock_quantity = $stock->value('quantity');

            //get stock id //instead we already have stock_id but picking from quered varible.
            $stock_id = $stock->value('id');

            if ($in_stock_quantity >= $items[$i]['quantity'] && $items[$i]['quantity'] > 0) {

                //adding current stock with new purchased product quantity
                $new_stock_quantity = $in_stock_quantity - $items[$i]['quantity'];

                $stock = Stock::findOrFail($stock_id);

                $stock->quantity = $new_stock_quantity;

                $stock->unit_id = $items[$i]['unit_id'];

                $stock->created_at = $timeStamp;

                $stock->updated_at = $timeStamp;

                if ($stock->save()) {

                    //set current invoice_id_count to store table
                    $store->invoice_id_count = $new_count_invoice_id;

                    if ($store->save()) {

                        $invoice_status_save = true;

                    } else {

                        $jsonResponse = ['message' => 'Failed updating the Data to the store.', 'status' => 'error'];

                    }
                } else {

                    $jsonResponse = ['message' => 'Failed Saving the Data to the Stock.', 'status' => 'error'];

                }

            } else {
                $jsonResponse = ['message' => 'You dont have Stock.', 'status' => 'error'];
            }

        }
        if ($invoice_status_save) {
            $CustomerTransaction = new CustomerTransaction();
            $CustomerTransaction->transaction_type = "sales";
            $CustomerTransaction->refId = $invoice->id;
            $CustomerTransaction->amount = $data['grand_total'];
            $CustomerTransaction->customer_id = $data['customer_id'];
            $CustomerTransaction->store_id = $data['store_id'];
            $CustomerTransaction->date = $data['invoice_date'];
            if ($CustomerTransaction->save()) {
                $jsonResponse = ['message' => 'Successfully created invoice & customer transactions', 'status' => 'success'];
            } else {

                $jsonResponse = ['message' => 'Error adding invoice to customer transaction.', 'status' => 'error'];

            }
        }
        return response()->json($jsonResponse);

    }
    public function update(Request $request)
    {

        // $this->authorize('hasPermission', 'edit_invoice');

    
        $store_id = Auth::user()->default_store;

        // //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.customer_name' => 'required | string| max:200',
            'info.customer_id' => 'required',
            'info.due_date' => 'required | date',
            'info.invoice_date' => 'required | date',

            'info.discount' => 'required | numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);
        $id = $request->id; //we will get invoice id here

        $invoice = Invoice::where('id', $id)->where('store_id', $store_id)->first();

        $items = collect($request->items)->transform(function ($item) {
            $item['line_total'] = $item['quantity'] * $item['price'];
            return new InvoiceDetail($item);
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
        // Get Invoice
        $Invoice = Invoice::where('id', $id)->where('store_id', $store_id)->first();

        //get invoice details
        $invoiceDetail = InvoiceDetail::where('invoice_id', $id)->get();

        $countItems = count($invoiceDetail);

        $check_save_stock = false;

        // $timeStamp=now();
        if ($countItems != 0) {

            for ($i = 0; $i < $countItems; $i++) {
                //get product id from each invoice details
                $p_id = $invoiceDetail[$i]['product_id'];

                $old_invoice_detail_qty = $invoiceDetail[$i]['quantity'];

                //finding stock to decrease the quantity of this invoice
                $stock = Stock::where('product_id', $p_id)->where('store_id', $store_id);

                $stock_id = $stock->value('id');

                $stock_qty = $stock->value('quantity');

                $old_stock_qty = $stock_qty + $old_invoice_detail_qty;

                $stock = Stock::where('id', $stock_id)->where('store_id', $store_id)->first();

                $stock->quantity = $old_stock_qty - $items[$i]['quantity'];

                if ($stock->save()) {
                    $check_save_stock = true;
                } else {
                    $check_save_stock = false;
                }
            }
            if ($check_save_stock) {

                $invoice->update($data);

                InvoiceDetail::where('invoice_id', $invoice->id)->delete();

                $invoice->invoiceDetail()->saveMany($items);

                $CustomerTransaction = CustomerTransaction::where('refID',$invoice->id)->where('store_id',$store_id)->first();
                // $CustomerTransaction->transaction_type = "sales";
                // $CustomerTransaction->refId = $invoice->id;
                $CustomerTransaction->amount = $data['grand_total'];
                $CustomerTransaction->customer_id = $data['customer_id'];
                // $CustomerTransaction->store_id = $data['store_id'];
                $CustomerTransaction->date = $data['invoice_date'];
                if ($CustomerTransaction->save()) {
                return response()->json(['message' => 'You have successfully updated the Invoice.', 'status' => 'success']);

                } else {
                return response()->json(['message' => 'Error adding invoice to customer transaction.', 'status' => 'success'],500);

                }


            } else {
                //saving stock fails
                return response()->json(['message' => 'Initial update to stock failed.', 'status' => 'error'],500);
            }

            // check stock save status and do following

        } else {

            return response()->json([
                'message' => 'Update Failed. There is no items in this invoice',
                'status' => 'error',
            ], 500);
        }

    }
    public function returnInvoice(Request $request)
    {

        // $this->authorize('hasPermission', 'return_invoice');

        $store_id = Auth::user()->default_store;

        // //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.supplier_name' => 'required | string| max:200',
            'info.due_date' => 'required | date',
            'info.invoice_date' => 'required | date',

            'info.discount' => 'required | numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);

        $id = $request->id; //invoice id

        $invoice = Invoice::where('id', $id)->where('store_id', $store_id)->first();

        $items = collect($request->items)->transform(function ($item) {
            $item['line_total'] = $item['quantity'] * $item['price'];
            return new InvoiceDetail($item);
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

        //for inserting in stock and altering if already has one initialized stock and previous stock
        $items_raw = collect($request->items); //collecting new items from the submit form

        $countItemsNew = count($items_raw); //get new items length of elements

        $timeStamp = now();

        //retriving old invoice records for the references
        $invoiceDetail_old = InvoiceDetail::where('invoice_id', $id)->get(); //get old data from the database

        $countItemsOld = count($invoiceDetail_old); //get old items length of elements

        for ($i = 0; $i < $countItemsOld; $i++) {

            $p_id = $items[$i]['product_id'];

            $stock = Stock::where('product_id', $p_id);

            //retirving current product-> stock quantity
            $in_stock_quantity = $stock->value('quantity');

            //get stock id
            $stock_id = $stock->value('id');

            //adding current stock with new purchased product quantity
            if ($in_stock_quantity >= $items[$i]['quantity']) {

                $new_stock_quantity = $in_stock_quantity - $items[$i]['quantity'];

                $stock = Stock::where('store_id', $store_id)->where('id', $stock_id)->first();

                $stock->quantity = $new_stock_quantity;

                $stock->unit_id = $items[$i]['unit_id'];

                $stock->created_at = $timeStamp;

                $stock->updated_at = $timeStamp;

                if ($stock->save()) {

                    $invoice->update($data);

                    InvoiceDetail::where('invoice_id', $invoice->id)->delete();

                    $invoice->InvoiceDetail()->saveMany($items);

                    return response()->json(['message' => 'You have successfully return the invoice.', 'status' => 'success']);

                }
            }
        }
        return response()->json(['message' => 'Failed while returning invoice. Check your stock quanity.', 'status' => 'error']);
    }

    public function show($id)
    {
        $store_id = Auth::user()->default_store;

        $invoice = Invoice::where('store_id',$store_id)->where('custom_invoice_id', $id)->with('invoiceDetail.product.unit')->with('customer')->first();
        if ($invoice) {
            return response()->json([
                'message' => 'Invoices fetched successfully',
                'invoice' => $invoice,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Error while retriving Invoice',
                'status' => 'danger',
            ], 500);
        }
    }

    public function destroy($id)
    {
        // $this->authorize('hasPermission', 'delete_invoice');

        $store_id = Auth::user()->default_store;


        // Get Invoice
        $Invoice = Invoice::where('id', $id)->where('store_id', $store_id)->first();

        //get invoice details
        $invoiceDetail = InvoiceDetail::where('invoice_id', $id)->get();

        $countItems = count($invoiceDetail);

        // $timeStamp=now();
        if ($countItems != 0) {

            for ($i = 0; $i < $countItems; $i++) {
                //get product id from each invoice details
                $p_id = $invoiceDetail[$i]['product_id'];

                $p_qty = $invoiceDetail[$i]['quantity'];

                //finding stock to decrease the quantity of this invoice
                $stock = Stock::where('product_id', $p_id)->where('store_id', $store_id);

                $stock_id = $stock->value('id');

                $stock_qty = $stock->value('quantity');

                $stock = Stock::where('id', $stock_id)->where('store_id', $store_id)->first();

                if ($stock_qty >= 0) {

                    $stock->quantity = $stock_qty + $p_qty;

                }
                if ($stock->save()) {

                    if ($Invoice->delete()) {

                        $CustomerTransaction = CustomerTransaction::where('refID',$Invoice->id)->where('store_id',$store_id)->first();
                            if($CustomerTransaction->delete()){
                                return response()->json([
                                    'message' => 'successfully Deleted',
                                    'status' => 'success',
                                ]);
                            }else{
                                return response()->json([
                                    'message' => 'Customer Transaction Delete Failed',
                                    'status' => 'error',
                                ]);
                            }
                     
                    } else {
                        return response()->json([
                            'message' => 'Invoice Delete Failed',
                            'status' => 'error',
                        ]);
                    }
                }

            }

        } else {

            if ($Invoice->delete()) {

                return response()->json([
                    'message' => 'Successfully Deleted',
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
    public function searchInvoices(Request $request)
    {

        // $this->authorize('hasPermission', 'search_invoice');

        $store_id = Auth::user()->default_store;


        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {

            // $queryResults=Estimate::where('customer_name','like','%'.$searchQuery.'%')->get();
            return InvoiceResource::collection(Invoice::where('store_id', $store_id)->where('customer_name', 'like', '%' . $searchKey . '%')->paginate(8));
        } else {
            return response()->json([
                'message' => 'Error while retriving Invoices. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
