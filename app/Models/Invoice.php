<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public function invoiceDetail()
    {
        return $this->hasMany('\App\Models\InvoiceDetail', 'invoice_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo('\App\Models\Customer', 'customer_id', 'id');
    }

    protected $fillable = ['invoice_date', 'due_date', 'customer_id', 'customer_name', 'sub_total', 'discount', 'tax_amount', 'grand_total', 'status', 'store_id', 'custom_invoice_id', 'note'];
}
