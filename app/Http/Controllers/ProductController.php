<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function searchProduct(Request $request)
    {


        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {

            return ProductResource::collection(Product::where('name', 'like', '%' . $searchKey . '%')->with('category')->with('unit')->get());

        } else {
            return response()->json([
                'msg' => 'Error while retriving Products. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }

    }
}
