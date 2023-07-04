<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory as Category;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductCategoryController extends Controller
{

    public function __construct()
    {

        $this->middleware('auth:api');

        // Auth::user()->name,

    }
    public function index(){
        $store_id = Auth::user()->default_store;

        return ProductCategoryResource::collection(Category::where('store_id',$store_id)->orderBy('updated_at', 'desc')->paginate(8));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'description' => 'required|string|max:400',

        ]);


        $store_id = Auth::user()->default_store;

        $store = Store::findOrFail($store_id);

        $category_id_count = $store->category_id_count;

        //explode category id from database

        $custom_category_id = explode('-', $category_id_count);

        $custom_category_id[1] = $custom_category_id[1] + 1; //increase category count

        //new custom_category_id
        $custom_category_id = implode('-', $custom_category_id);
        $category = new Category();
        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->store_id = $store_id;
        $category->custom_category_id = $custom_category_id;

        if ($category->save()) {

            $store->category_id_count = $custom_category_id;

            if ($store->save()) {

                return response()->json([
                    'msg' => 'Category added successfully',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'msg' => 'Failed to update data to store ',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'msg' => 'Category fail to add ',
                'status' => 'error',
            ]);
        }
    }
    public function update(Request $request)
    {

        $this->validate($request, [
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'description' => 'required|string|max:400',

        ]);

        $store_id = Auth::user()->default_store;

        $id = $request->input('id'); //get id from edit modal
        $category = Category::where('id', $id)->where('store_id',$store_id)->first();
        $category->name = $request->input('name');
        $category->description = $request->input('description');
     
        if ($category->save()) {
            return response()->json([
                'msg' => 'Category updated successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Category fail to update ',
                'status' => 'error',
            ]);
        }
    }
    public function destroy($id)
    {
        $store_id = Auth::user()->default_store;

        $category = Category::where('id', $id)->where('store_id',$store_id)->first();
        if ($category->delete()) {
            return response()->json([
                'msg' => 'Category deleted successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Category deletion failed',
                'status' => 'error',
            ]);
        }
    }
    public function show($id)
    {
        $store_id = Auth::user()->default_store;

        $category = Category::where('id', $id)->where('store_id',$store_id)->first();
        if ($category) {
            return response()->json([
                'msg' => 'Category fetched successfully',
                'category' => $category,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Category',
                'status' => 'error',
            ]);
        }
    }

    public function searchCategories(Request $request)
    {
        $store_id=Auth::user()->default_store;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return ProductCategoryResource::collection(Category::where('store_id',$store_id)->where('name', 'like', '%' . $searchKey . '%')->get());
        } else {
            return response()->json([
                'msg' => 'Error while retriving Category. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
