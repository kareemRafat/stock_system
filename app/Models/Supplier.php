<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address'];

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function wallet()
    {
        return $this->hasMany(SupplierWallet::class);
    }
}
