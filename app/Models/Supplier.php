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

    public function getBalanceAttribute()
    {
        // use query fore better performance
        // calculate the balance based on the wallet transactions
        return $this->wallet()
            ->selectRaw("
                        SUM(
                            CASE
                                WHEN type = 'debit' THEN -amount
                                WHEN type = 'invoice' THEN -amount
                                WHEN type = 'credit' THEN amount
                                ELSE 0
                            END
                        ) as balance
                    ")
            ->value('balance') ?? 0;
    }
}
