<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    public function index(){

        $store_id = Auth::user()->default_store;

        return UnitResource::collection(Unit::where('store_id',$store_id)->orderBy('updated_at', 'desc')->paginate(8));
    }
}
