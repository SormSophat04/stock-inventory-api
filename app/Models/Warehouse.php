<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $primaryKey = 'warehouse_id';

    protected $fillable = [
        'name',
        'location',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'warehouse_id', 'warehouse_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'warehouse_id', 'warehouse_id');
    }
}
