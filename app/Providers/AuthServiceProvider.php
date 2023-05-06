<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Passport::tokensCan([
            'user' => 'Can Do everything that is granted',
           
        ]);


        // Gate::define('hasPermission', function ($user, $action) {

        //     $permissions = $user->roles()->first();

        //     $permissions = $permissions->permissions()->first()->name;

        //     $permissions = explode(',', $permissions); //seperate name string by ',' and push them to array

        //     if (in_array($action, $permissions) || in_array('all', $permissions)) {

        //         return true;
        //     } else {
        //         return false;
        //     }

        // });

        // Gate::define('hasStore', function ($user, $store) {

        //     $check = $user->stores()->where('store_id', $store);

        //     if ($check->first()) {

        //         return true;
        //     } else {
        //         return false;
        //     }
        // });
    }
}
