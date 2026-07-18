<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class LorryMonthlySalesProductExport implements FromArray, WithEvents
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

    /**
     * Required by FromArray, but all real content is written in the
     * AfterSheet event below so we get full control over merges/borders/widths.
     */
    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getColumnDimension('A')->setWidth(16);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(9);
                $sheet->getColumnDimension('D')->setWidth(14);
                $sheet->getColumnDimension('E')->setWidth(10);
                $sheet->getColumnDimension('F')->setWidth(10);

                $dateLabel = 'Date: ' . Carbon::parse($this->dateFrom)->format('d-m-Y')
                    . ' - ' . Carbon::parse($this->dateTo)->format('d-m-Y');

                $row = 1;

                if (empty($this->blocks) || count($this->blocks) === 0) {
                    $sheet->setCellValue("A{$row}", 'No sales data found for this period.');
                    $sheet->mergeCells("A{$row}:F{$row}");
                    return;
                }

                foreach ($this->blocks as $block) {
                    $blockStart = $row;

                    // Lorry title
                    $sheet->setCellValue("A{$row}", 'Lorry: ' . $block['lorry']);
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
                    $row++;

                    // Date range
                    $sheet->setCellValue("A{$row}", $dateLabel);
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $row++;

                    // Column headers
                    $headerRow = $row;
                    $sheet->setCellValue("A{$row}", 'Product Code');
                    $sheet->setCellValue("B{$row}", 'Product Name');
                    $sheet->setCellValue("C{$row}", 'Qty');
                    $sheet->setCellValue("D{$row}", 'Unit Price (RM)');
                    $sheet->mergeCells("E{$row}:F{$row}");
                    $sheet->setCellValue("E{$row}", 'Total Sales (RM)');
                    $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$row}:F{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E8E8E8');
                    $row++;

                    // Product rows
                    foreach ($block['products'] as $p) {
                        $sheet->setCellValue("A{$row}", $p['code']);
                        $sheet->setCellValue("B{$row}", $p['name']);
                        $sheet->setCellValue("C{$row}", $p['qty']);
                        $sheet->setCellValue("D{$row}", $p['unit_price']);
                        $sheet->mergeCells("E{$row}:F{$row}");
                        $sheet->setCellValue("E{$row}", $p['total']);
                        $row++;
                    }

                    // Lorry total row
                    $sheet->setCellValue("A{$row}", $block['lorry'] . ' - TOTAL');
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->setCellValue("C{$row}", $block['qty']);
                    $sheet->mergeCells("E{$row}:F{$row}");
                    $sheet->setCellValue("E{$row}", $block['total']);
                    $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                    $row++;

                    $blockEnd = $row - 1;

                    // Right-align numeric columns for this block
                    $sheet->getStyle("C{$headerRow}:E{$blockEnd}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Border around this lorry's whole table
                    $sheet->getStyle("A{$blockStart}:F{$blockEnd}")
                        ->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    $row++; // blank spacer row between lorries
                }

                // Grand total
                $sheet->setCellValue("A{$row}", 'GRAND TOTAL');
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("C{$row}", $this->grandQty);
                $sheet->mergeCells("E{$row}:F{$row}");
                $sheet->setCellValue("E{$row}", $this->grandTotal);
                $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8E8E8');
                $sheet->getStyle("C{$row}:E{$row}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("A{$row}:F{$row}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
