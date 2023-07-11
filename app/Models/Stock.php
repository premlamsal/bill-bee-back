<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    public function product()
    {
        return $this->belongsTo('\App\Models\Product', 'product_id', 'id');
    }
    public function unit()
    {
        return $this->belongsTo('\App\Models\Unit', 'unit_id', 'id');
    }
    protected $fillable = ['product_id', 'quantity', 'unit_id', 'price'];
}
