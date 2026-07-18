<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class LorryMonthlySalesProductExport implements FromView
{
    protected $blocks;
    protected $dateFrom;
    protected $dateTo;
    protected $grandQty;
    protected $grandTotal;

    public function __construct($blocks, $dateFrom, $dateTo, $grandQty, $grandTotal)
    {
        $this->blocks     = $blocks;
        $this->dateFrom   = $dateFrom;
        $this->dateTo     = $dateTo;
        $this->grandQty   = $grandQty;
        $this->grandTotal = $grandTotal;
    }

    public function view(): View
    {
        return view('exports.lorry_monthly_sales_product', [
            'blocks'     => $this->blocks,
            'dateFrom'   => $this->dateFrom,
            'dateTo'     => $this->dateTo,
            'grandQty'   => $this->grandQty,
            'grandTotal' => $this->grandTotal,
        ]);
    }
}
