<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function user()
    {

        return $this->belongsTo('App\Models\User');
    }

    public function permissions()
    {

        return $this->belongsToMany('App\Models\Permission', 'role_permission', 'role_id', 'permission_id');
    }

    public function hasPermission($permission)
    {

        $permissions = $this->permissions()->first()->value('actions');

        $permissions = explode(',', $permissions);

        if (in_array($permission, $permissions)) {

            return true;
        }
        return false;
    }
}
