<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }

    .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #333; padding-bottom: 10px; }
    .header h1 { font-size: 15px; font-weight: bold; }
    .header p  { font-size: 10px; color: #555; }

    .report-title { text-align: center; margin-bottom: 12px; }
    .report-title h2 { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .report-title span { font-size: 10px; color: #666; }

    .filter-bar { background: #f5f5f5; border: 1px solid #ddd; padding: 6px 10px; margin-bottom: 14px; font-size: 10px; }
    .filter-bar span { margin-right: 16px; }

    .customer-box { border: 1px solid #ccc; background: #f9f9f9; padding: 8px 12px; margin-bottom: 14px; }
    .customer-box .name { font-size: 13px; font-weight: bold; }
    .customer-box .info { font-size: 10px; color: #555; margin-top: 2px; }

    .section-label { font-size: 11px; font-weight: bold; background: #fff; color: #050505; padding: 4px 8px; margin-bottom: 0; }

    .monthly-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .monthly-table th { background: #e8e8e8; color: #000; padding: 5px 8px; text-align: left; font-size: 10px; }
    .monthly-table td { padding: 4px 8px; border: 1px solid #ddd; font-size: 10px; }
    .monthly-table tr:nth-child(even) td { background: #f9f9f9; }
    .monthly-table .total-row td { font-weight: bold; background: #e8e8e8; border-top: 2px solid #333; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }

    .detail-table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 6px; }
    .detail-table th { background: #e8e8e8; color: #000; padding: 5px 6px; text-align: left; font-size: 10px; }
    .detail-table td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .invoice-block { page-break-inside: avoid; }

    .mt-10 { margin-top: 10px; }
    .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 8px; text-align: center; font-size: 9px; color: #888; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    @if($company)
        <h1>{{ $company->name }}</h1>
        <p>{{ implode(', ', array_filter([$company->address1 ?? null, $company->address2 ?? null, $company->address3 ?? null, $company->address4 ?? null])) }}</p>
    @else
        <h1>Customer Purchase History</h1>
    @endif
</div>

<div class="report-title">
    <h2>Customer Purchase History Report</h2>
    <span>Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}</span><br>
    <span style="font-size:9px;color:#888;">Generated: {{ now()->format('d-m-Y H:i:s') }}</span>
</div>

{{-- Customer Info --}}
<div class="customer-box">
    <div class="name">Customer:  {{ $customer->company }}</div>
    <div class="info">
        @if($customer->name) Contact: {{ $customer->name }} &nbsp;|&nbsp; @endif
        @if($customer->phone) Phone: {{ $customer->phone }} &nbsp;|&nbsp; @endif
        @if($customer->email) Email: {{ $customer->email }} @endif
    </div>
</div>

{{-- Active Filters --}}
<div class="filter-bar">
    <span><strong>Lorry:</strong> {{ $filterLorry }}</span>
    <span><strong>Payment:</strong> {{ $filterPayment }}</span>
</div>

{{-- Monthly Summary --}}
<div class="section-label">Monthly Purchase Summary</div>
<table class="monthly-table">
    <thead>
        <tr>
            <th>Month</th>
            <th class="text-center">No. of Invoices (Frequency)</th>
            <th class="text-right">Total Qty</th>
            <th class="text-right">Total Amount (RM)</th>
        </tr>
    </thead>
    <tbody>
        @php $totalFreq = 0; $totalQty = 0; $totalAmt = 0; @endphp
        @forelse($monthlySummary as $row)
        @php
            $totalFreq += $row['frequency'];
            $totalQty  += $row['total_qty'];
            $totalAmt  += $row['total_amount'];
        @endphp
        <tr>
            <td>{{ $row['month'] }}</td>
            <td class="text-center">{{ $row['frequency'] }}</td>
            <td class="text-right">{{ $row['total_qty'] }}</td>
            <td class="text-right">{{ number_format($row['total_amount'], 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center" style="padding:10px;">No purchase records found.</td></tr>
        @endforelse
        @if(count($monthlySummary))
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="text-center">{{ $totalFreq }}</td>
            <td class="text-right">{{ $totalQty }}</td>
            <td class="text-right">{{ number_format($totalAmt, 2) }}</td>
        </tr>
        @endif
    </tbody>
</table>

@php
    $detailColgroup = '
        <colgroup>
            <col style="width:11%"><col style="width:11%"><col style="width:10%"><col style="width:10%">
            <col style="width:33%"><col style="width:7%"><col style="width:8%"><col style="width:10%">
        </colgroup>
    ';
@endphp

{{-- Product Detail --}}
<div class="section-label mt-10">Product Purchase Detail</div>
<table class="detail-table">
    {!! $detailColgroup !!}
    <thead>
        <tr>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Driver</th>
            <th>Payment</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Amount</th>
        </tr>
    </thead>
</table>

@forelse($invoices as $invoice)
    @php
        $paymentLabel = $paymentLabels[$invoice->paymentterm] ?? '-';
        $firstItem = true;
        $rowCount = $invoice->invoicedetail->count() ?: 1;
        $invoiceTotal = $invoice->invoicedetail->sum('totalprice');
    @endphp
    <table class="detail-table invoice-block">
        {!! $detailColgroup !!}
        <tbody>
            @forelse($invoice->invoicedetail as $detail)
            <tr>
                @if($firstItem)
                <td rowspan="{{ $rowCount }}">{{ $invoice->invoiceno }}</td>
                <td rowspan="{{ $rowCount }}">{{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</td>
                <td rowspan="{{ $rowCount }}">{{ $invoice->driver?->name ?? '-' }}</td>
                <td rowspan="{{ $rowCount }}">{{ $paymentLabel }}</td>
                @php $firstItem = false; @endphp
                @endif
                <td>
                    {{ $detail->product?->name ?? '-' }}
                    @if($detail->remark === 'FOC')
                        <span style="color:#e67e00;font-size:9px;">[FOC]</span>
                    @endif
                </td>
                <td class="text-right">{{ $detail->quantity }}</td>
                <td class="text-right">{{ number_format($detail->price, 2) }}</td>
                <td class="text-right">{{ number_format($detail->totalprice, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td>{{ $invoice->invoiceno }}</td>
                <td>{{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</td>
                <td>{{ $invoice->driver?->name ?? '-' }}</td>
                <td>{{ $paymentLabel }}</td>
                <td colspan="4" class="text-center" style="color:#999;">No items</td>
            </tr>
            @endforelse
            <tr style="background:#f0f0f0;">
                <td colspan="7" style="text-align:right;font-weight:bold;">Invoice Total:</td>
                <td class="text-right" style="font-weight:bold;">{{ number_format($invoiceTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>
@empty
    <table class="detail-table">
        {!! $detailColgroup !!}
        <tbody>
            <tr><td colspan="8" class="text-center" style="padding:12px;">No invoices found.</td></tr>
        </tbody>
    </table>
@endforelse

<div class="footer">
    This report was generated automatically &mdash; {{ now()->format('d-m-Y H:i:s') }}
</div>

</body>
</html>
