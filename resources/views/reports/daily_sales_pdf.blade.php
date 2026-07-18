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

    .section-label { font-size: 11px; font-weight: bold; background: #fff; color: #050505; padding: 4px 8px; margin-bottom: 0; }

    .summary-table { width: 50%; border-collapse: collapse; margin-bottom: 14px; }
    .summary-table th { background: #e8e8e8; color: #000; padding: 5px 8px; text-align: left; font-size: 10px; }
    .summary-table td { padding: 4px 8px; border: 1px solid #ddd; font-size: 10px; }
    .summary-table tr:nth-child(even) td { background: #f9f9f9; }
    .summary-table .total-row td { font-weight: bold; background: #e8e8e8; border-top: 2px solid #333; }
    .text-right { text-align: right; }

    .invoice-table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 6px; }
    .invoice-table th { background: #e8e8e8; color: #000; padding: 5px 6px; text-align: left; font-size: 10px; }
    .invoice-table td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .invoice-table .item-row td { background: #fff; }
    .invoice-table .inv-total td { background: #f0f0f0; font-weight: bold; }
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
        <h1>Daily Sales Report</h1>
    @endif
</div>

<div class="report-title">
    <h2>Daily Sales Report</h2>
    <span>Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}</span><br>
    <span style="font-size:9px;color:#888;">Generated: {{ now()->format('d-m-Y H:i:s') }}</span>
</div>

{{-- Active Filters --}}
<div class="filter-bar">
    <span><strong>Lorry:</strong> {{ $filterLorry }}</span>
    <span><strong>Customer:</strong> {{ $filterCustomer }}</span>
    <span><strong>Payment:</strong> {{ $filterPayment }}</span>
</div>

{{-- Payment Summary --}}
<div class="section-label">Payment Summary</div>
<table class="summary-table">
    <thead>
        <tr>
            <th>Payment Method</th>
            <th>Amount (RM)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($paymentLabels as $key => $label)
        <tr>
            <td>{{ $label }}</td>
            <td class="text-right">{{ number_format($breakdown[$key] ?? 0, 2) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tbody>
</table>

@php
    $invoiceColgroup = '
        <colgroup>
            <col style="width:11%"><col style="width:10%"><col style="width:14%">
            <col style="width:10%"><col style="width:10%"><col style="width:25%">
            <col style="width:7%"><col style="width:6%"><col style="width:7%">
        </colgroup>
    ';
@endphp

{{-- Invoice Details --}}
<div class="section-label mt-10">Invoice Details</div>
<table class="invoice-table">
    {!! $invoiceColgroup !!}
    <thead>
        <tr>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Driver</th>
            <th>Payment</th>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Amount</th>
        </tr>
    </thead>
</table>

@forelse($invoices as $invoice)
    @php
        $invoiceTotal = $invoice->invoicedetail->sum('totalprice');
        $paymentLabel = $paymentLabels[$invoice->paymentterm] ?? '-';
        $firstItem = true;
        $rowCount = $invoice->invoicedetail->count() ?: 1;
    @endphp
    <table class="invoice-table invoice-block">
        {!! $invoiceColgroup !!}
        <tbody>
            @forelse($invoice->invoicedetail as $detail)
            <tr class="item-row">
                @if($firstItem)
                <td rowspan="{{ $rowCount }}">{{ $invoice->invoiceno }}</td>
                <td rowspan="{{ $rowCount }}">{{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</td>
                <td rowspan="{{ $rowCount }}">{{ $invoice->customer?->company ?? '-' }}</td>
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
            <tr class="item-row">
                <td>{{ $invoice->invoiceno }}</td>
                <td>{{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</td>
                <td>{{ $invoice->customer?->company ?? '-' }}</td>
                <td>{{ $invoice->driver?->name ?? '-' }}</td>
                <td>{{ $paymentLabel }}</td>
                <td colspan="4" style="text-align:center;color:#999;">No items</td>
            </tr>
            @endforelse
            <tr class="inv-total">
                <td colspan="8" style="text-align:right;font-weight:bold;">Invoice Total:</td>
                <td class="text-right">{{ number_format($invoiceTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>
@empty
    <table class="invoice-table">
        {!! $invoiceColgroup !!}
        <tbody>
            <tr><td colspan="9" style="text-align:center;padding:12px;">No invoices found for this period.</td></tr>
        </tbody>
    </table>
@endforelse

<div class="footer">
    This report was generated automatically &mdash; {{ now()->format('d-m-Y H:i:s') }}
</div>

</body>
</html>
