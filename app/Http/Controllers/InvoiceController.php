<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(){

        return InvoiceResource::collection(Invoice::with('invoiceDetail')->orderBy('updated_at', 'desc')->paginate(8));
    }
   
     public function store(Request $request){
        
        
        $store_id=1;
        $store_tax=0.13;
        
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
    if(array_key_exists('discount', $data)){
        $data['grand_total'] = $data['sub_total'] + $data['tax_amount'] - $data['discount'];
    }

    $data['sub_total'] = $items->sum('line_total');

    $data['discount'] = 0;

    $data['status'] = 0;

    $data['tax_amount'] = $data['sub_total'] * $store_tax;

    $data['grand_total'] = $data['sub_total'] + $data['tax_amount'];

    $data['store_id'] = $store_id;

    //transaction started

    $invoice = Invoice::create($data);

    $invoice->invoiceDetail()->saveMany($items);


    $jsonResponse = ['msg' => 'Successfully created invoice & customer transactions', 'status' => 'success'];
    return response()->json($jsonResponse);

    
    }
}
