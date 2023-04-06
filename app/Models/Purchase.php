<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    public function purchaseDetail()
    {
        return $this->hasMany('\App\Models\PurchaseDetail', 'purchase_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo('\Models\App\Supplier', 'supplier_id', 'id');
    }

    protected $fillable = ['purchase_date','purchase_reference_id', 'due_date', 'image', 'supplier_id', 'supplier_name', 'sub_total', 'discount', 'tax_amount', 'grand_total', 'status', 'store_id', 'custom_purchase_id', 'note'];
}
