<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {

        $user_id = Auth::user()->id;

        $user = User::findOrFail($user_id);

        $this->validate($request, [

            'name'        => 'required|string|max:20',
            'address'        => 'required|string|max:20',
            'phone'        => 'required|string|max:20',
            'mobile'       => 'required|string|max:20',
            'email'       => 'required|email|max:100',
            'url'        => 'required|string|max:100',
            'tax_number'        => 'required|string|max:20',
            'tax_percentage'        => 'required|string|max:20',
            'profit_percentage'        => 'required|string|max:20',

        ]);

        $store = new Store();
        $store->name = $request->input('name');
        $store->address = $request->input('address');
        $store->phone = $request->input('phone');
        $store->mobile = $request->input('mobile');
        $store->email = $request->input('email');
        $store->url = $request->input('url');
        $store->tax_number = $request->input('tax_number');
        $store->tax_percentage = $request->input('tax_percentage');
        $store->profit_percentage = $request->input('profit_percentage');

        if ($store->save()) {

            $user->stores()->attach($store);

            $role = new Role();

            $role->name = 'owner_role'; //owner has all privilledge to do.

            $role->store_id = $store->id; //storing store if from recently saved store.

            if ($role->save()) {

                $user->roles()->attach($role);

                $permission           = new Permission();
                $permission->name     = 'owner_permission';
                $permission->actions     = 'all';
                $permission->store_id = $store->id;
                if ($permission->save()) {

                    $role->permissions()->attach($permission);

                    $user = User::findOrFail(Auth::user()->id);
                    $user->default_store = $store->id;
                    if ($user->save()) {
                        //success saving user default store 
                        return response()->json([
                            'message' => 'Successfully created store with roles, permissions with default store',
                            'status' => 'success',
                        ]);
                    } else {
                        return response()->json([
                            'message' => 'Failed creating store with default store',
                            'status' => 'error',
                        ]);
                    }
                } else {
                    return response()->json([
                        'message' => 'Failed creating store with permissions ',
                        'status' => 'error',
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Failed creating store with roles ',
                    'status' => 'error',
                ]);
            }
        } else {
            //error while saving store data to database
            return response()->json([
                'message' => 'failed creating store',
                'status' => 'danger',
            ]);
        }
    }
    public function show($id)
    {

        $store_id = $id;

        $store = Store::findOrFail($store_id);
        if ($store) {
            return response()->json([
                'message' => 'Store fetched successfully',
                'store' => $store,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'failed fetching store data',
                'store' => $store,
                'status' => 'error',
            ]);
        }
    }
    public function getUserStore()
    {

         $user_default_store_id = Auth::user()->default_store;

      
        $store = Store::findOrFail($user_default_store_id);
        if ($store) {
            return response()->json([
                'message' => 'Store fetched successfully',
                'store' => $store,
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'message' => 'failed fetching store data',
                'store' => $store,
                'status' => 'error',
            ]);
        }
    }
}
