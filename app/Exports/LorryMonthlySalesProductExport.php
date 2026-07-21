<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class LorryMonthlySalesProductExport implements FromArray, WithEvents
{
    protected $tables;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($tables, $dateFrom, $dateTo)
    {
        $this->tables   = $tables;
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
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

                $monthLabel = Carbon::parse($this->dateFrom)->format('F Y');

                $row = 1;

                if (empty($this->tables) || count($this->tables) === 0) {
                    $sheet->setCellValue("A{$row}", 'No lorries found.');
                    $sheet->mergeCells("A{$row}:B{$row}");
                    return;
                }

                foreach ($this->tables as $table) {
                    $products    = $table['products'];
                    $hasData     = count($products) > 0;
                    // A=Date, one column per product, then TOTAL RM (minimum 2 columns when there's no data)
                    $lastColIdx  = $hasData ? (2 + count($products)) : 2;
                    $lastColLtr  = Coordinate::stringFromColumnIndex($lastColIdx);

                    $blockStart = $row;

                    // Lorry title
                    $sheet->setCellValue("A{$row}", 'Lorry: ' . $table['lorry']);
                    $sheet->mergeCells("A{$row}:{$lastColLtr}{$row}");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
                    $row++;

                    // Month
                    $sheet->setCellValue("A{$row}", $monthLabel);
                    $sheet->mergeCells("A{$row}:{$lastColLtr}{$row}");
                    $row++;

                    if (!$hasData) {
                        $sheet->setCellValue("A{$row}", 'No sales recorded for this lorry in this period.');
                        $sheet->mergeCells("A{$row}:{$lastColLtr}{$row}");
                        $row++;

                        $blockEnd = $row - 1;
                        $sheet->getStyle("A{$blockStart}:{$lastColLtr}{$blockEnd}")
                            ->getBorders()->getAllBorders()
                            ->setBorderStyle(Border::BORDER_THIN);
                        $row++; // blank spacer row between lorries
                        continue;
                    }

                    // Header row: Date | <product columns> | TOTAL RM
                    $headerRow = $row;
                    $sheet->getColumnDimension('A')->setWidth(10);
                    $sheet->setCellValue("A{$row}", 'Date');

                    $colIdx = 2;
                    foreach ($products as $product) {
                        $colLtr = Coordinate::stringFromColumnIndex($colIdx);
                        $sheet->getColumnDimension($colLtr)->setWidth(14);
                        $sheet->setCellValue("{$colLtr}{$row}", $product->name);
                        $colIdx++;
                    }

                    $sheet->getColumnDimension($lastColLtr)->setWidth(14);
                    $sheet->setCellValue("{$lastColLtr}{$row}", 'TOTAL RM');

                    $sheet->getStyle("A{$row}:{$lastColLtr}{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$row}:{$lastColLtr}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E8E8E8');
                    $sheet->getStyle("A{$row}:{$lastColLtr}{$row}")
                        ->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $row++;

                    // One row per day — Date column shows the day-of-month only
                    $dataStartRow = $row;
                    foreach ($table['rows'] as $dayRow) {
                        $sheet->setCellValue("A{$row}", (int) Carbon::parse($dayRow['date'])->format('j'));

                        $colIdx = 2;
                        foreach ($products as $product) {
                            $colLtr = Coordinate::stringFromColumnIndex($colIdx);
                            $sheet->setCellValue("{$colLtr}{$row}", $dayRow['cells'][$product->id] ?? 0);
                            $colIdx++;
                        }

                        $sheet->setCellValue("{$lastColLtr}{$row}", $dayRow['total']);
                        $row++;
                    }

                    // Column totals row: qty totals per product, RM grand total in the last column
                    $sheet->setCellValue("A{$row}", 'TOTAL');
                    $colIdx = 2;
                    foreach ($products as $product) {
                        $colLtr = Coordinate::stringFromColumnIndex($colIdx);
                        $sheet->setCellValue("{$colLtr}{$row}", $table['column_totals'][$product->id] ?? 0);
                        $colIdx++;
                    }
                    $sheet->setCellValue("{$lastColLtr}{$row}", $table['grand_total']);
                    $sheet->getStyle("A{$row}:{$lastColLtr}{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$row}:{$lastColLtr}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E8E8E8');
                    $row++;

                    $blockEnd = $row - 1;

                    // Right-align numeric columns (everything except the Date column)
                    $secondColLtr = Coordinate::stringFromColumnIndex(2);
                    $sheet->getStyle("{$secondColLtr}{$headerRow}:{$lastColLtr}{$blockEnd}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // TOTAL RM column always shows 2 decimal places
                    $sheet->getStyle("{$lastColLtr}{$dataStartRow}:{$lastColLtr}{$blockEnd}")
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                    // Border around this lorry's whole table
                    $sheet->getStyle("A{$blockStart}:{$lastColLtr}{$blockEnd}")
                        ->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    $row++; // blank spacer row between lorries
                }
            },
        ];
    }
}
