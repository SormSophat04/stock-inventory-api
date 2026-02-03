<?php

namespace App\Imports;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockImport implements ToCollection, WithHeadingRow
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        // Validate the entire collection first (optional, but recommended)
        Validator::make($rows->toArray(), [
            '*.warehouse_id' => 'required|exists:warehouses,warehouse_id',
            '*.product_id'   => 'required|exists:products,product_id',
            '*.supplier_id'  => 'required|exists:suppliers,supplier_id',
            '*.quantity'     => 'required|integer|min:1',
            '*.unit_price'   => 'required|numeric|min:0',
        ])->validate();

        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                $stock = Stock::where('warehouse_id', $row['warehouse_id'])
                    ->where('product_id', $row['product_id'])
                    ->first();

                if ($stock) {
                    // Update existing stock
                    $stock->quantity += $row['quantity'];
                    $stock->unit_price = $row['unit_price'];
                    $stock->invoice = $row['invoice'] ?? $stock->invoice; // Update invoice if provided
                    $stock->note = $row['note'] ?? $stock->note;
                    $stock->save();
                } else {
                    // Create new stock
                    $stock = Stock::create([
                        'warehouse_id' => $row['warehouse_id'],
                        'product_id'   => $row['product_id'],
                        'supplier_id'  => $row['supplier_id'],
                        'created_by'   => $this->userId,
                        'invoice'      => $row['invoice'] ?? null,
                        'unit_price'   => $row['unit_price'],
                        'quantity'     => $row['quantity'],
                        'note'         => $row['note'] ?? null,
                    ]);
                }

                // Record the movement
                StockMovement::create([
                    'product_id'   => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'type'         => 'IMPORT', // Distinguish this from regular 'IN'
                    'reference_no' => $stock->invoice,
                    'quantity'     => $row['quantity'], // The amount added, not total
                    'note'         => 'Bulk Import via Excel',
                    'created_by'   => $this->userId,
                ]);
            });
        }
    }
}
