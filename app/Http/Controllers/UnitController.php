<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnitResource;
use App\Models\Store;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:api');

        // Auth::user()->name,

    }
    public function index(){

        $store_id = Auth::user()->default_store;

        return UnitResource::collection(Unit::where('store_id',$store_id)->orderBy('updated_at', 'desc')->paginate(8));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'long_name' => 'required|regex:/^[\pL\s\-]+$/u',
            'short_name' => 'required|regex:/^[\pL\s\-]+$/u',

        ]);


        $store_id = Auth::user()->default_store;

        $store = Store::findOrFail($store_id);

        $unit_id_count = $store->unit_id_count;

        //explode unit id from database

        $custom_unit_id = explode('-', $unit_id_count);

        $custom_unit_id[1] = $custom_unit_id[1] + 1; //increase unit count

        //new custom_unit_id
        $custom_unit_id = implode('-', $custom_unit_id);
        $unit = new Unit();
        $unit->short_name = $request->input('short_name');
        $unit->long_name = $request->input('long_name');
        $unit->custom_unit_id = $custom_unit_id;
        $unit->store_id = $store_id;
        if ($unit->save()) {

            $store->unit_id_count = $custom_unit_id;

            if ($store->save()) {

                return response()->json([
                    'message' => 'Unit added successfully',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to update data to store ',
                    'status' => 'error',
                ]);
            }
        } else {
            return response()->json([
                'message' => 'Unit fail to add ',
                'status' => 'error',
            ]);
        }
    }
    public function update(Request $request)
    {

        $this->validate($request, [
            'long_name' => 'required|regex:/^[\pL\s\-]+$/u',
            'short_name' => 'required|regex:/^[\pL\s\-]+$/u',

        ]);

        $store_id = Auth::user()->default_store;

        $id = $request->input('id'); //get id from edit modal
        $unit = Unit::where('id', $id)->where('store_id',$store_id)->first();
        $unit->short_name = $request->input('short_name');
        $unit->long_name = $request->input('long_name');
        if ($unit->save()) {
            return response()->json([
                'message' => 'Unit updated successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Unit fail to update ',
                'status' => 'error',
            ]);
        }
    }
    public function destroy($id)
    {
        $store_id = Auth::user()->default_store;

        $unit = Unit::where('id', $id)->where('store_id',$store_id)->first();
        if ($unit->delete()) {
            return response()->json([
                'message' => 'Unit deleted successfully',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Unit deletion failed',
                'status' => 'error',
            ]);
        }
    }
    public function show($id)
    {
        $store_id = Auth::user()->default_store;

        $unit = Unit::where('id', $id)->where('store_id',$store_id)->first();
        if ($unit) {
            return response()->json([
                'message' => 'Unit fetched successfully',
                'unit' => $unit,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'Error while retriving Unit',
                'status' => 'error',
            ]);
        }
    }

    public function searchUnits(Request $request)
    {
        $store_id=Auth::user()->default_store;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return UnitResource::collection(Unit::where('store_id',$store_id)->where('name', 'like', '%' . $searchKey . '%')->get());
        } else {
            return response()->json([
                'message' => 'Error while retriving Unit. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
