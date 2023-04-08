<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(Product::orderBy('updated_at', 'desc')->with('unit')->with('category')->paginate(8));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'product_cat_id' =>  'required|numeric',
            'unit_id' => 'required|numeric',
            'cp' => 'required|numeric',
            'sp' => 'required|numeric',
            'description' => 'required|string|max:200',
            'opening_stock' => 'required|numeric',

        ]);


        //let store =1;
        $store_id = 1;
        
        $product = new Product();
        $product->name = $request->input('name');
        $product->product_cat_id = $request->input('product_cat_id');
        $product->unit_id = $request->input('unit_id');
        $product->cp = $request->input('cp');
        $product->sp = $request->input('sp');
        $product->description = $request->input('description');
        $product->opening_stock = $request->input('opening_stock');
        $product->store_id = $store_id;
        if ($product->save()) {
            return response()->json([
                'msg' => 'Product added successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Product fail to add ',
                'status' => 'error',
            ]);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'product_cat_id' =>  'required|numeric',
            'unit_id' => 'required|numeric',
            'cp' => 'required|numeric',
            'sp' => 'required|numeric',
            'description' => 'required|string|max:200',
            'opening_stock' => 'required|numeric',

        ]);


        //let store =1;
        $store_id = 1;

        $id = $request->input('id'); //get id from edit modal
        $product = Product::where('id', $id)->first();
        $product->name = $request->input('name');
        $product->product_cat_id = $request->input('product_cat_id');
        $product->unit_id = $request->input('unit_id');
        $product->cp = $request->input('cp');
        $product->sp = $request->input('sp');
        $product->description = $request->input('description');
        $product->opening_stock = $request->input('opening_stock');
        $product->store_id = $store_id;
        if ($product->save()) {
            return response()->json([
                'msg' => 'Product updated successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Product fail to update ',
                'status' => 'error',
            ]);
        }
    }



    public function show($id){

        $product = Product::where('id', $id)->with('category')->with('unit')->first();
        if ($product) {
            return response()->json([
                'msg'=>'Product fetched successfully',
                'product' => $product,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Product',
                'status' => 'error',
            ]);
        }
    }



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
