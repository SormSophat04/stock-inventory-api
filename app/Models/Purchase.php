<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $primaryKey = 'purchase_id';

    // ENSURE ALL THESE ARE PRESENT
    protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'invoice_no',
        'total_amount',
        'payment_status', // <--- This is often missing
        'created_by',
        'purchase_date',
    ];

    // ... keep your relationships ...
    public function items() { return $this->hasMany(PurchaseItems::class, 'purchase_id', 'purchase_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id'); }
}
