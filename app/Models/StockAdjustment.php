<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    public function product()
    {
        return $this->belongsTo('\App\Models\Product', 'product_id', 'id');
    }

    public function stock()
    {
        return $this->belongsTo('\App\Models\Stock', 'stock_id', 'id');
    }
}
