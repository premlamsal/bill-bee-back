<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Notifications\RegistrationNotification;
use App\Notifications\VerificationNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');

    }

    public function index(Request $request){

        $user_id= Auth::user()->id;

        return UserResource::collection(User::where('id','!=',$user_id)->with('roles')->orderBy('updated_at', 'desc')->paginate(8));

    }
    public function store(Request $request)
    {

        $store_id = Auth::user()->stores[0]->id;

        $store = Auth::user()->stores[0];

        $this->validate($request, [

            'name'     => 'required|string|max:20',

            'email'    => 'required|email|max:100',

            'role_id'  => 'required|integer',

            'password' => 'required',
            // 'password'=>'required| min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%]).*$/',
            //English uppercase characters (A – Z)

            // English lowercase characters (a – z)

            // Base 10 digits (0 – 9)

            // Non-alphanumeric (For example: !, $, #, or %)

            // Unicode characters
        ]);

        $user           = new User();
        $user->name     = $request->input('name');
        $user->email    = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->activation_token = Str::random(60);
        $user->last_login='Not Logged In Yet';
        $role = Role::findOrFail($request->input('role_id'));

        if ($user->save()) {

            $user->roles()->attach($role);

            $user->stores()->attach($store);

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

    public function update(Request $request)
    {

        $store_id = Auth::user()->stores[0]->id;

        $store = Auth::user()->stores[0];

        $this->validate($request, [

            'name'        => 'required|string|max:20',

            'email'       => 'required|email|max:100',

            'role_id_old' => 'required|integer',

            'role_id'     => 'required|integer',

            // 'password'    => 'required',

            // 'password'=>'required| min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%]).*$/',
            //English uppercase characters (A – Z)

            // English lowercase characters (a – z)

            // Base 10 digits (0 – 9)

            // Non-alphanumeric (For example: !, $, #, or %)

            // Unicode characters
        ]);

        $user_id = $request->input('id');

        $user = User::findOrFail($user_id);

        $user->name = $request->input('name');

        $user->email = $request->input('email');

        if($request->input('password')){
            $user->password = bcrypt($request->input('password'));
        }

        if ($user->save()) {

            if($request->input('role_id_old')){
                $role_old = Role::findOrFail($request->input('role_id_old'));

                $user->roles()->detach($role_old);
            }
          

            $role = Role::findOrFail($request->input('role_id'));

            $user->roles()->attach($role);

            return response()->json([
                'msg'    => 'Data updated',
                'status' => 'success',
            ]);

        } else {

            return response()->json([
                'msg'    => 'Error updating Data',
                'status' => 'danger',
            ]);

        }
    }

    public function show($id)
    {

        // $this->authorize('hasPermission', 'all'); //all permission belongs to owner only

        $user = User::where('id', $id)->with('roles')->first();

        return response()->json([
            'msg'    => 'Successfuly fetched Data',
            'user'   => $user,
            'status' => 'success',
        ]);
    }

    public function destroy($id)
    {
   
        // $this->authorize('hasPermission', 'all'); //all permission belongs to owner only

        $user = User::findOrFail($id); //finding passed user refrence

        $role_name_of_passed_user = $user->roles[0]->name;

        $store_id_of_passed_user = $user->stores[0]->id;

        //checking logged in user belong to that passed user id store or not

        $this->authorize('hasStore', $store_id_of_passed_user);

        // $this->authorize('hasPermission','delete_user');

        if ($role_name_of_passed_user != 'owner') {


            if ($user->delete()) {
                return response()->json([
                    'msg'    => 'Successfully Deleted',
                    'status' => 'success',
                ]);
            } else {
                return response()->json([
                    'msg'    => 'Error while deleting data',
                    'status' => 'danger',
                ]);
            }
        } else {
            return response()->json([
                'msg'    => 'You can\'t delete owner',
                'status' => 'danger',
            ]);
        }

    }

    public function searchUsers(Request $request)
    {

        $this->authorize('hasPermission', 'all'); //all permission belongs to owner only

        $user = User::findOrFail(Auth::user()->id);

        $store_id = $user->stores[0]->id;

        $searchKey = $request->input('searchQuery');
        if ($searchKey != '') {
            return UserResource::collection(User::where('name', 'like', '%' . $searchKey . '%')->paginate(8));
        } else {
            return response()->json([
                'msg'    => 'Error while retriving Users. No Data Supplied as key.',
                'status' => 'danger',
            ]);
        }
    }

}
