<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {

        // $this->authorize('hasPermission', 'view_permissions');

        $store_id= Auth::user()->stores[0]->id;
        
        $permission=Permission::where('actions', '!=', 'all')->where('store_id',$store_id)->paginate(8);
        
        // $permission->actions=explode(',',$permission->value('actions'));
        
        return PermissionResource::collection($permission);


        // return response()->json([
        //     'msg'    => $store_id,
        //     'status' => 'success',
        // ]);

    }

    public function store(Request $request)
    {

        // $this->authorize('hasPermission', 'add_permission');

        $store_id= Auth::user()->stores[0]->id;

        $this->validate($request, [

            'name' => 'required',
            'actions' => 'required',

        ]);

        $permission = new Permission();

        $permission->name = $request->input('name');

        $permission_array = $request->input('actions');//will get check permission array

        $permission->actions =  $permission_array; //joining array elements in string with ',' seperation

        $permission->store_id = $store_id;

        if ($permission->save()) {

            return response()->json([
                'msg'    => 'You have successfully added the information.',
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg'    => 'Opps! My Back got cracked while working in Database',
                'status' => 'error',
            ]);
        }

    }

    public function show($id)
    {

        // $this->authorize('hasPermission', 'show_permission');

        $store_id= Auth::user()->stores[0]->id;

        $permission = Permission::where('id', $id)->where('store_id', $store_id);
        $permission->name= $permission->value('name');
        $permission->id= $permission->value('id');
        $permission->actions= explode(',',$permission->value('actions'));
        if ($permission) {
            return response()->json([
                'msg' => 'Permission fetched successfully',
                'permission' => $permission,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'msg' => 'Error while retriving Permission',
                'status' => 'error',
            ]);
        }

    }

    public function update(Request $request)
    {

        // $this->authorize('hasPermission', 'edit_permission');

        $store_id= Auth::user()->stores[0]->id;

        $this->validate($request, [

            'name' => 'required',
            'actions'=>'required',

        ]);

        $id = $request->input('id');

        $permission = Permission::where('id', $id)->where('store_id', $store_id)->first();

        $permission->name = $request->input('name');


        //joining array elements in string with ',' seperation

        $permission_array = $request->input('actions');//will get check permission array


        // $permission->actions=implode(',', $permission_array);

        $permission->actions=$permission_array;


        if ($permission->save()) {

            return response()->json([

                'msg'    => "Record Updated successfully",

                'status' => 'success',
            ]);
        } else {

            return response()->json([

                'msg'    => 'Error Updating Data',

                'status' => 'error',
            ]);
        }
    }

    public function destroy($id)
    {

        // $this->authorize('hasPermission', 'delete_permission');

       $store_id= Auth::user()->stores[0]->id;

        $permission = Permission::where('id', $id)->where('store_id', $store_id)->first();

        if ($permission->delete()) {

            return response()->json([

                'msg'    => 'successfully Deleted',

                'status' => 'success',
            ]);
        } else {
            return response()->json([

                'msg'    => 'Error while deleting data',

                'status' => 'error',
            ]);
        }
    }

    public function searchPermissions(Request $request)
    {

        // $this->authorize('hasPermission', 'search_permission');
        $store_id= Auth::user()->stores[0]->id;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return PermissionResource::collection(Permission::where('store_id', $store_id)->where('short_name', 'like', '%' . $searchKey . '%')->where('store_id', $store_id)->paginate(8));
        } else {
            return response()->json([
                'msg'    => 'Error while retriving Permissions. No Data Supplied as key.',
                'status' => 'error',
            ]);
        }
    }
}
