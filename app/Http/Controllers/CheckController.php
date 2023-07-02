<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth:api');
    }

    public function stores()
    {

        $user = User::findOrFail(Auth::user()->id);

        $stores = $user->stores;

        $default_store = Auth::user()->default_store;

        return response()->json(['default_store' => $default_store, 'stores' => $stores, 'status' => 'success']);
    }
    // public function checkUserForStore()
    // {

    //     $user_id = Auth::user()->id; //get logged user id

    //     $store = UserStore::where('user_id', $user_id)->get(); //get store information for the user

    //     if (count($store) > 0) {

    //         print_r($store);

    //     } else {

    //         print_r("You don't have any store. Insted Create One");
    //     }

    // }

    public function checkPermissions()
    {


        $permissions = Auth::user()->roles()->first();

        if ($permissions) {

            $permissions = $permissions->permissions()->first()->actions;

            $permissions = explode(',', $permissions); //seperate name string by ',' and push them to array

        }

        // print_r($permissions);

        // $permissions=['view_customer','edit_customer','delete_customer','add_customer','is_user'];

        return response()->json([

            'permissions' => $permissions,
            'status'      => 'success',
        ]);
    }
    public function hasStore()
    {

        $stores = Auth::user()->stores()->first();

        return response()->json([

            'stores' => $stores,
            'status'      => 'success',
        ]);
    }
    public function saveUserDefaultStore(Request $request)
    {
        $this->validate($request, [
            'selected_store' =>  'required|numeric',
        ]);
     
        $input_selected_store = $request->input('selected_store');

        if (Auth::user()->hasStore($input_selected_store)) {
        
            $user = User::findOrFail(Auth::user()->id);
        
            $user->default_store = $input_selected_store;
        
            if ($user->save()) {
                //success message
                return response()->json([
                   
                    'message' => 'Store Updated successfully',
                   
                    'status' => 'success',
                ]);
            } else {
                //error saving default store to current user. Contact admin for more information.
                return response()->json([
                
                    'message' => 'Error saving default store to current user. Contact admin for more information',
                
                    'status' => 'error',
                ]);
            }
        } else {
            //there is no store like that or don't have permission to access that 
            return response()->json([
               
                'message' => 'There is no store like that or don\'t have permission to access that ',
               
                'status' => 'error',
            ]);
        }
    }
}
