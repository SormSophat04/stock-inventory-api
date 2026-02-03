<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit_id',
        'sell_price',
        'reorder_level',
        'status',
        'image',
    ];

    // --- FIX START: Add a virtual 'cost_price' for the frontend ---
    protected $appends = ['cost_price'];

    public function getCostPriceAttribute()
    {
        // Since you don't have a buy price in DB, we default to 0
        // Or you could return $this->sell_price if you prefer.
        return 0;
    }
    // --- FIX END ---

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function sales()
    {
        return $this->hasMany(SaleItems::class, 'product_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_id', 'product_id');
    }
}
