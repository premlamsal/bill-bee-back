<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(){
        return UnitResource::collection(Unit::orderBy('updated_at', 'desc')->paginate(8));
    }
}
