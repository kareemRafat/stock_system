<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'total_amount',
        'notes',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    protected static function booted(): void
    {
        static::saved(function ($invoice) {
            $total = $invoice->items()->sum('subtotal');
            $invoice->updateQuietly([
                'total_amount' => $total,
            ]);
        });
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = self::generateUniqueInvoiceNumber();
            }
        });
    }

    public static function generateUniqueInvoiceNumber(): string
    {
        $prefix = 'INV-';

        for ($i = 0; $i < 5; $i++) {
            $number = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (!self::where('invoice_number', $number)->exists()) {
                return $number;
            }
        }

        // Fallback to a timestamp if all attempts fail
        return $prefix . now()->format('ymdHis');
    }

    public function getTotalAmountAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }
}
