<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Sale::with(['customer', 'warehouse', 'user'])->get();
    }

    public function headings(): array
    {
        return [
            'Invoice No',
            'Date',
            'Customer',
            'Warehouse',
            'Total Amount',
            'Status',
            'Payment Method',
            'Created By'
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->invoice_no,
            $sale->sale_date,
            $sale->customer ? $sale->customer->name : 'Walk-in',
            $sale->warehouse ? $sale->warehouse->name : 'N/A',
            $sale->total_amount,
            $sale->status,
            $sale->payment_method,
            $sale->user ? $sale->user->name : 'N/A'
        ];
    }
}
