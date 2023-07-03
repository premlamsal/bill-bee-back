<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Store;
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

        // //validation
        $this->validate($request, [

            'info.note' => 'required | string |max:200',
            'info.customer_name' => 'required | string| max:200',
            'info.customer_id' => 'required',
            'info.due_date' => 'required | date',
            'info.invoice_date' => 'required | date',
            'info.customer_id' => 'required',
            'info.discount' => 'numeric| max:200',

            'items.*.product_name' => 'required | string |max:200',
            'items.*.price' => 'required | numeric',
            'items.*.quantity' => 'required | numeric',

        ]);

        $invoice_status_save = false;

        $store_id = Auth::user()->default_store;

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
        if (array_key_exists('discount', $data)) {
            $data['grand_total'] = $data['sub_total'] + $data['tax_amount'] - $data['discount'];
        }

        $data['sub_total'] = $items->sum('line_total');

        $data['discount'] = 0;

        $data['status'] = 0;

        $data['tax_amount'] = $data['sub_total'] * $store_tax;

        $data['grand_total'] = $data['sub_total'] + $data['tax_amount'];

        $data['store_id'] = $store_id;

        $data['custom_invoice_id'] = $new_count_invoice_id;

        //transaction started

        $invoice = Invoice::create($data);

        $invoice->invoiceDetail()->saveMany($items);

        //set current invoice_id_count to store table
        $store->invoice_id_count = $new_count_invoice_id;

        if ($store->save()) {
            $invoice_status_save = true;
        } else {

            $jsonResponse = ['msg' => 'Failed updating the Data to the store.', 'status' => 'error'];
        }
        if ($invoice_status_save) {

            $jsonResponse = ['msg' => 'Successfully created invoice & update store data ', 'status' => 'success'];
        }
        return response()->json($jsonResponse);
    }
    public function update(Request $request)
    {
        //validation
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


        $store_id = Auth::user()->default_store;

        $id = $request->id; //we will get invoice id here

        $invoice = Invoice::where('custom_invoice_id', $id)->where('store_id', $store_id)->first();

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


        $invoice->update($data);

        InvoiceDetail::where('invoice_id', $invoice->id)->delete();

        $invoice->InvoiceDetail()->saveMany($items);


        if ($items->isEmpty()) {
            return response()
                ->json([
                    'items_empty' => ['One or more Item is required.'],
                ], 422);
        }


        if ($invoice) {
            return response()->json([
                'msg' => 'Invoices updated successfully',
                'invoice' => $invoice,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while updating Invoice',
                'status' => 'danger',
            ], 500);
        }
    }
    public function show($id)
    {
        $store_id = Auth::user()->default_store;

        $invoice = Invoice::where('store_id',$store_id)->where('custom_invoice_id', $id)->with('invoiceDetail.product.unit')->with('customer')->first();
        if ($invoice) {
            return response()->json([
                'msg' => 'Invoices fetched successfully',
                'invoice' => $invoice,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Invoice',
                'status' => 'danger',
            ], 500);
        }
    }
}
