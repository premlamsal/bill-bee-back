<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(){
        return ProductCategoryResource::collection(ProductCategory::orderBy('updated_at', 'desc')->paginate(8));
    }
}
