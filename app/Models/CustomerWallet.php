<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerWallet extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerWalletFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'invoice_id',
        'note',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
