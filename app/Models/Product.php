<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function category()
    {
        return $this->belongsTo('\App\Models\ProductCategory', 'product_cat_id', 'id');
    }
    public function stock()
    {
        return $this->hasMany('\App\Models\Stock', 'product_id', 'id');
    }
    public function unit()
    {
        return $this->belongsTo('\App\Models\Unit', 'unit_id', 'id');
    }
}
