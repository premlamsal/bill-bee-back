<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:api');

        // Auth::user()->name,

    }
    public function index()
    {
        $store_id = Auth::user()->default_store;

        return ProductResource::collection(Product::where('store_id',$store_id)->orderBy('updated_at', 'desc')->with('unit')->with('category')->paginate(8));
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4048',


        ]);



        $store_id = Auth::user()->default_store;

        $store = Store::findOrFail($store_id);

        $product_id_count = $store->product_id_count;

        //explode product id from database

        $custom_product_id = explode('-', $product_id_count);

        $custom_product_id[1] = $custom_product_id[1] + 1; //increase product count

        //new custom_product_id
        $custom_product_id = implode('-', $custom_product_id);

        $product = new Product();
        $product->name = $request->input('name');
        $product->product_cat_id = $request->input('product_cat_id');
        $product->unit_id = $request->input('unit_id');
        $product->cp = $request->input('cp');
        $product->sp = $request->input('sp');
        $product->description = $request->input('description');
        $product->opening_stock = $request->input('opening_stock');
        $product->custom_product_id = $custom_product_id;
        $product->store_id = $store_id;
        // $product->image = "https://avatars.githubusercontent.com/u/24312128?v=4";
        //put some image code here

        if ($request->hasFile('image')) {
            $imageName = 'img/' . time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('img'), $imageName);
            $product->image = $imageName;
        }

        $product->store_id = $store_id;


        if ($product->save()) {

            $stock = new Stock();
            if (($request->input('opening_stock')) > 0) {
                $stock->quantity = $request->input('opening_stock');
            } else {
                $stock->quantity = 0.00;
            }
            $stock->product_id = $product->id;
            
            $stock->unit_id = $request->input('unit_id');

            $stock->store_id = $store_id;

            $stock->price = $request->input('cp');

            if ($stock->save()) {
                //set current product_id_count to store table
                $store->product_id_count = $custom_product_id;

                if ($store->save()) {

                    return response()->json([
                        'message' => 'You have successfully created the product.',
                        'status' => 'success',
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Error saving data to store.',
                        'status' => 'error',
                    ]);
                }

            } else {
                return response()->json([
                    'message' => 'Error saving data to stock.',
                    'status' => 'error',
                ]);
            }

        } else {
            return response()->json([
                'message' => 'Product fail to add ',
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
        $store_id = Auth::user()->default_store;

        $id = $request->input('id'); //get id from edit modal
        $product = Product::where('store_id',$store_id)->where('id', $id)->first();
        $product->name = $request->input('name');
        $product->product_cat_id = $request->input('product_cat_id');
        $product->unit_id = $request->input('unit_id');
        $product->cp = $request->input('cp');
        $product->sp = $request->input('sp');
        $product->description = $request->input('description');
        $product->opening_stock = $request->input('opening_stock');

        $product->store_id = $store_id;

        if ($request->hasFile('image')) {

            $img_ext = $request->image->getClientOriginalExtension();

            $checkExt = array("jpg", "png", "jpeg");

            if (in_array($img_ext, $checkExt)) {

                $imageName = 'img/' . time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->move(public_path('img'), $imageName);
                $product->image = $imageName;
            } else {
                return response()->json([
                    'message' => 'Opps! My Back got cracked while working in Database',
                    'status' => 'error',
                ]);
            }
        }
        if ($product->update()) {
            return response()->json([
                'message' => 'Product updated successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Product fail to update ',
                'status' => 'error',
            ]);
        }
    }



    public function show($id)
    {
        $store_id = Auth::user()->default_store;

        $product = Product::where('store_id',$store_id)->where('custom_product_id', $id)->with('category')->with('unit')->first();
        if ($product) {
            return response()->json([
                'message' => 'Product fetched successfully',
                'product' => $product,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Error while retriving Product',
                'status' => 'error',
            ]);
        }
    }



    // public function searchProduct(Request $request)
    // {
    //     $store_id = Auth::user()->default_store;

    //     $searchKey = $request->input('searchQuery');
    //     if ($searchKey != '') {

    //         return ProductResource::collection(Product::where('store_id',$store_id)->where('name', 'like', '%' . $searchKey . '%')->with('category')->with('stock')->with('unit')->get());
    //     } else {
    //         return response()->json([
    //             'message' => 'Error while retriving Products. No Data Supplied as key.',
    //             'status' => 'error',
    //         ]);
    //     }
    // }
    public function searchProduct(Request $request)
    {

        // $this->authorize('hasPermission', 'search_product');

        $store_id = Auth::user()->default_store;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            $product=Product::where('name', 'like', '%' . $searchKey . '%')->where('store_id', $store_id)->with('unit')->with('category')->get();
            $product_suggestion=array();
            for($i=0;$i<$product->count();$i++){
                $temp=array();
                $temp=Stock::where('product_id',$product[$i]->id)->with('product.unit')->get();
                $product_suggestion[$i]=$temp;
            }
            return response()->json([
                'data' => $product_suggestion[0],
            ]);
        
        } else {
            return response()->json([
                'msg' => 'Error while retriving Products. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }

    }

}
