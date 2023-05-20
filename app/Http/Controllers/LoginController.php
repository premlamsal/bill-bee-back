<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\RegistrationNotification;
use App\Notifications\VerificationNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
     
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $validated = $request->validate([
            'email' => 'required|email:rfc,dns|max:255',
            'password' => 'required',
        ]);

        if (User::where('email', $email)->count() <= 0) {
            return response(array("message" => "Credentials does not exist"), 400);
        }

        $user = User::where('email', $email)->first();


        if (password_verify($password, $user->password)) {
            $user->last_login = Carbon::now();
            $user->save();
            return response(array("message" => "Login In Successful", "data" => [
       
                "store"=>$user->stores,

                "user" => $user,
                // Below the user key passed as the second parameter sets the role
                // anyone with the auth token would have only user access rights
                "token" => $user->createToken('Personal Access Token', ['user'])->accessToken,
            ]), 200);
        } else {
            return response(array("message" => "Wrong Credentials."), 400);
        }
    }
    public function register(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $name = $request->input('name');

        $rules = [
            'email' => 'required|email|unique:users|unique:users|max:255',
            'name' => 'required|max:255',
            'password' => ['required'],
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first()], 400);
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        //other filed will be updated later when user redirected to dashboard
        //user will be redirected to form to input remaining details
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->activation_token = Str::random(60);

        if ($user->save()) {

            // $user->user_type = "user";

            // $user->notify(new VerificationNotification($user));

            // $user->notify(new RegistrationNotification($user));

            return response()->json(['message' => 'User acount created', 'user' => $user]);
        } else {
            return response()->json(['Some error occured while creating User']);
        }
    }

    public function verifyUser(Request $request)
    {
        $user = User::where('activation_token', $request->token)->first();
        
        if (!$user) {
            return response()->json([
                'title' => 'This activation token is invalid.',
                'description' => 'Please contact Billbee Technical team for help.',

            ], 404);
        }
        $user->active = true;
        $user->activation_token = '';

        if ($user->save()) {

            $user->notify(new VerificationNotification($user));

            $user->notify(new RegistrationNotification($user));

            return response()->json([
                'title' => 'Succefully verified',
                'description' => 'Dear ' . $user->name . ' Please login to your profile and complete it.!',
            ], 200);
        }
    }
}
