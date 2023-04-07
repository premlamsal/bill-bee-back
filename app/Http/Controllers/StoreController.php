<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function show($id){

        $store_id=$id;

        $store= Store::findOrFail($store_id);
        if($store){
            return response()->json([
                'msg' => 'Store fetched successfully',
                'store' => $store,
                'status' => 'success',
            ]);
        }else{
            return response()->json([
                'msg' => 'failed fetching store data',
                'store' => $store,
                'status' => 'error',
            ]);
        }




    }
}
