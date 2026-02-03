<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
      use HasFactory;

      protected $primaryKey = 'transfer_item_id';

      public $timestamps = false;

      protected $fillable = [
            'transfer_id',
            'product_id',
            'quantity',
      ];

      // Relationship to StockTransfer
      public function transfer()
      {
            return $this->belongsTo(StockTransfer::class, 'transfer_id', 'transfer_id');
      }

      // Relationship to Product
      public function product()
      {
            return $this->belongsTo(Product::class, 'product_id', 'product_id');
      }
}
