<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierTransactionController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:api');

    }
    public function index($supplier_id){
        
        // $this->authorize('hasPermission','view_supplier_transactions');

        $store_id = Auth::user()->default_store;
        
        $supplier_id_db= Supplier::where('custom_supplier_id',$supplier_id)->where('store_id',$store_id)->value('id');

        $SupplierTransaction=SupplierTransaction::where('supplier_id',$supplier_id_db)->where('store_id',$store_id)->get();
        $transactions=array();
        $balance=0.00;
        for($i=0;$i<$SupplierTransaction->count();$i++){
            if($SupplierTransaction[$i]->transaction_type==='opening_balance'){
                $opening_balance=$SupplierTransaction[$i]->amount;
                $balance=$opening_balance; 

            }
            if($SupplierTransaction[$i]->transaction_type==='purchase'){
                $balance=$balance + $SupplierTransaction[$i]->amount;
            }
            if($SupplierTransaction[$i]->transaction_type==='purchase_return'){
                $balance=$balance + $SupplierTransaction[$i]->amount;
            }
            if($SupplierTransaction[$i]->transaction_type==='payment'){
                $balance = $balance - $SupplierTransaction[$i]->amount;
            }
            $balance= number_format((float)$balance, 2, '.', '');
            $SupplierTransaction[$i]->balance=$balance;
            $transactions[$i]=$SupplierTransaction[$i];
        }

        return response()->json(['transactions'=>$transactions]);
    }
}
