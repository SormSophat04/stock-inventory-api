<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $primaryKey = 'transfer_id';

    protected $fillable = [
        'from_warehouse',
        'to_warehouse',
        'note',
        'created_by',
    ];

    // Relationship to Source Warehouse
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse', 'warehouse_id');
    }

    // Relationship to Destination Warehouse
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse', 'warehouse_id');
    }

    // Relationship to Items
    public function items()
    {
        return $this->hasMany(TransferItem::class, 'transfer_id', 'transfer_id');
    }

    // Relationship to Creator
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
