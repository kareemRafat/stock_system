<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnInvoice extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'return_invoice_number',
        'original_invoice_number',
        'original_invoice_id',
        'total_amount',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnInvoiceItem::class);
    }
}
