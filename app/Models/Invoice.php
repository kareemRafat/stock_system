<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function returnInvoices(): HasMany
    {
        return $this->hasMany(ReturnInvoice::class, 'original_invoice_id');
    }

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

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('Y-m-d h:i:s A') : null,
        );
    }

    protected function createdDate(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : null,
        );
    }

    protected function createdTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i a') : null,
        );
    }

    protected function createdTime12Hour(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->created_at ? Carbon::parse($this->created_at)->format('h:i A') : null,
        );
    }

    public function hasReturns(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->returnInvoices()->exists(),
        );
    }

    public function returnsCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->returnInvoices()->count(),
        );
    }
}
