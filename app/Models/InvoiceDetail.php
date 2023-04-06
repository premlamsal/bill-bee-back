<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    public function invoice()
    {
        return $this->belongsTo('\App\Models\Invoice', 'invoice_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo('\App\Models\Unit', 'unit_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo('\App\Models\Product', 'product_id', 'id');
    }
    
    protected $fillable = ['invoice_id', 'product_id','stock_id','product_name', 'quantity', 'unit_id', 'price', 'line_total'];
}
