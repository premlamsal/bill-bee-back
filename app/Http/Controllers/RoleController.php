<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Undefined;

class RoleController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:api');

    }

    public function index()
    {

        $store_id = Auth::user()->default_store;

        //this will send roles without role type: owner
        return RoleResource::collection(Role::where('store_id', $store_id)->where('name', '!=', 'owner')->with('permissions')->paginate(8));

        //this will send all roles including role type:owner
        // return RoleResource::collection(Role::where('store_id', $store_id)->with('permissions')->paginate(8));

    }

    public function store(Request $request)
    {

        $store_id = Auth::user()->default_store;


        $this->validate($request, [

            'name' => 'required|string|max:10',

        ]);

        $role = new Role();

        $role->name = $request->input('name');

        $role->store_id = $store_id;

        $permission = Permission::findOrFail($request->input('permission_id'));

        if ($role->save()) {

            $role->permissions()->attach($permission);

            return response()->json([
                'msg'    => 'Data Saved',
                'status' => 'success',
            ]);
        } else {

            return response()->json([
                'msg'    => 'Error Saving Data',
                'status' => 'danger',
            ]);
        }
    }

    public function show($id)
    {

        // $this->authorize('hasPermission', 'show_role');

        $store_id = Auth::user()->default_store;


        $role = Role::where('id', $id)->where('store_id', $store_id)->with('permissions')->first();

        return response()->json([
            'role'   => $role,
            'msg'    => 'Successfuly fetched Data',
            'status' => 'success',
        ]);
    }

    public function update(Request $request)
    {


        $store_id = Auth::user()->default_store;


        $this->validate($request, [

            'name' => 'required|string|max:10',

        ]);

        $role = Role::findOrFail($request->input('id'));

        $role->name = $request->input('name');

        $role->store_id = $store_id;

        if ($role->save()) {

            if($request->input('permission_id_old')!="undefined")
            {
            $permission_old = Permission::findOrFail($request->input('permission_id_old'));

            // Detach a single role from the user...
            $role->permissions()->detach($permission_old);
            }

            $permission = Permission::findOrFail($request->input('permission_id'));
            //attach new role....
            $role->permissions()->attach($permission);

            return response()->json([
                'msg'    => 'Data updated success',
                'status' => 'success',
            ]);
        } else {

            return response()->json([
                'msg'    => 'Error Saving Data',
                'status' => 'danger',
            ]);
        }
    }

    public function destroy($id)
    {


        $store_id = Auth::user()->default_store;


        $role = Role::where('id', $id)->where('store_id', $store_id)->first();

        if ($role->delete()) {

            $permission_id = $role->permissions()->value('permission_id');
            if($permission_id){
                $permissions = Permission::findOrFail($permission_id);

                // print_r($permission_id);
                // Detach a single role from the user...
                $role->permissions()->detach($permissions);
            }
         
            return response()->json([

                'msg'    => 'successfully Deleted',

                'status' => 'success',
            ]);
        } else {
            return response()->json([

                'msg'    => 'Error while deleting data',

                'status' => 'danger',
            ]);
        }
    }

    public function searchPermissions(Request $request)
    {


        $store_id = Auth::user()->default_store;


        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return PermissionResource::collection(Permission::where('store_id', $store_id)->where('short_name', 'like', '%' . $searchKey . '%')->where('store_id', $store_id)->paginate(8));
        } else {
            return response()->json([
                'msg'    => 'Error while retriving Permissions. No Data Supplied as key.',
                'status' => 'danger',
            ]);
        }
    }
}
