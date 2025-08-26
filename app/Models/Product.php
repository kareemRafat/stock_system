<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discount',
        'stock_quantity',
        'unit',
        'production_price',
        'type'
    ];

    public function getFinalPriceAttribute()
    {
        if ($this->discount > 0) {
            $final = $this->attributes['price'] - ($this->attributes['price'] * $this->discount / 100);

            return number_format($final, 2, '.', '');
        }

        return number_format($this->attributes['price'], 2, '.', '');
    }
}
