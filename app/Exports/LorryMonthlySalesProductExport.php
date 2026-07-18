<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LorryMonthlySalesProductExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return collect($this->rows)->map(function ($row) {
            return [
                'Lorry'       => $row['lorry'],
                'Product Code' => $row['code'],
                'Product Name' => $row['name'],
                'Qty'         => $row['qty'],
                'Unit Price (RM)' => $row['unit_price'],
                'Total Sales (RM)' => $row['total'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Lorry',
            'Product Code',
            'Product Name',
            'Qty',
            'Unit Price (RM)',
            'Total Sales (RM)',
        ];
    }
}
