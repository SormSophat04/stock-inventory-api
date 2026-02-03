<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Product([
            'name'          => $row['name'],
            'sku'           => $row['sku'] ?? 'SKU-'.time().rand(10,99), // Fallback SKU
            'barcode'       => $row['barcode'] ?? null,
            'category_id'   => $row['category_id'] ?? 1, // Default to 1 if missing
            'brand_id'      => $row['brand_id'] ?? 1,    // Default to 1 if missing
            'unit_id'       => $row['unit_id'] ?? 1,     // Default to 1 if missing
            'sell_price'    => $row['sell_price'] ?? 0,
            'reorder_level' => $row['reorder_level'] ?? 10,
            'status'        => $row['status'] ?? 'active',
        ]);
    }
}
