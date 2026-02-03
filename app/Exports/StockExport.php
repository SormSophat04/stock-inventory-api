<?php

namespace App\Exports;

use App\Models\Stock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Stock::with(['warehouse', 'product', 'supplier'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Warehouse',
            'Product',
            'Supplier',
            'Invoice Ref',
            'Quantity',
            'Unit Price',
            'Total Value',
            'Note',
            'Last Updated'
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->id,
            $stock->warehouse->name ?? 'N/A', // Assuming 'name' column exists
            $stock->product->name ?? 'N/A',
            $stock->supplier->name ?? 'N/A',
            $stock->invoice,
            $stock->quantity,
            $stock->unit_price,
            $stock->quantity * $stock->unit_price, // Calculate total value
            $stock->note,
            $stock->updated_at->format('Y-m-d H:i'),
        ];
    }
}
