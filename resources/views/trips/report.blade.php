<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }

    .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 10px; }
    .header h1 { font-size: 15px; font-weight: bold; }
    .header p  { font-size: 10px; color: #555; }
    .report-title { text-align: center; margin-bottom: 14px; }
    .report-title h2 { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .report-title span { font-size: 10px; color: #666; }

    /* Section label */
    .section-label {
        font-size: 11px; font-weight: bold; background: #ffffff; color: #050505;
        padding: 4px 8px; margin-bottom: 0;
    }

    /* Generic info table */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .info-table td { padding: 4px 8px; border: 1px solid #ccc; font-size: 10px; }
    .info-table td.label { font-weight: bold; background: #f5f5f5; width: 30%; }

    /* Summary table */
    .summary-table { width: 50%; border-collapse: collapse; margin-bottom: 14px; }
    .summary-table th { background: #e8e8e8; color: #000000; padding: 5px 8px; text-align: left; font-size: 10px; }
    .summary-table td { padding: 4px 8px; border: 1px solid #ddd; font-size: 10px; }
    .summary-table tr:nth-child(even) td { background: #f9f9f9; }
    .summary-table .total-row td { font-weight: bold; background: #e8e8e8; border-top: 2px solid #333; }

    /* Stock movement table */
    .stock-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .stock-table th { background: #e8e8e8; color: #000; padding: 5px 6px; text-align: center; font-size: 10px; }
    .stock-table th.col-product { text-align: left; }
    .stock-table td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; text-align: center; }
    .stock-table td.col-product { text-align: left; }
    .stock-table tr:nth-child(even) td { background: #f9f9f9; }
    .stock-table .neg { color: #c0392b; }
    .stock-table .pos { color: #27ae60; }

    /* Invoice table */
    .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .invoice-table th { background: #e8e8e8; color: #000000; padding: 5px 6px; text-align: left; font-size: 10px; }
    .invoice-table td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .invoice-table .inv-header td { background: #eef2ff; font-weight: bold; }
    .invoice-table .item-row td { background: #fff; }
    .invoice-table .inv-total td { background: #f0f0f0; font-weight: bold; text-align: right; }
    .invoice-table .text-right { text-align: right; }

    .mt-10 { margin-top: 10px; }
    .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 8px; text-align: center; font-size: 9px; color: #888; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    @if($company)
        <h1>{{ $company->name }}</h1>
        <p>
            {{ implode(', ', array_filter([$company->address1, $company->address2, $company->address3, $company->address4])) }}
        </p>
    @else
        <h1>Daily Sales Report</h1>
    @endif
</div>

{{-- Report Title --}}
<div class="report-title">
    <h2>Daily Sales Report</h2>
    <span>Generated: {{ now()->format('d-m-Y H:i:s') }}</span>
</div>

{{-- Trip Information --}}
<div class="section-label">Trip Information</div>
<table class="info-table">
    <tr>
        <td class="label">Driver</td>
        <td>{{ $startTrip?->driver?->name ?? $endTrip->driver?->name ?? '-' }}</td>
        <td class="label">Lorry</td>
        <td>{{ $startTrip?->lorry?->lorryno ?? $endTrip->lorry?->lorryno ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Kelindan</td>
        <td>{{ $startTrip?->kelindan?->name ?? $endTrip->kelindan?->name ?? '-' }}</td>
        <td class="label">Closing Cash</td>
        <td>RM {{ number_format($endTrip->cash ?? 0, 2) }}</td>
    </tr>
    <tr>
        <td class="label">Start Time</td>
        <td>{{ $startTime ? $startTime->format('d-m-Y H:i:s') : '-' }}</td>
        <td class="label">End Time</td>
        <td>{{ $endTime->format('d-m-Y H:i:s') }}</td>
    </tr>
    <tr>
        <td class="label">Duration</td>
        <td colspan="3">
            @if($duration)
                {{ $duration->h }} hour(s) {{ $duration->i }} minute(s)
            @else
                -
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Diesel (RM)</td>
        <td>{{ $endTrip->diesel !== null ? number_format($endTrip->diesel, 2) : '-' }}</td>
        <td class="label">Toll (RM)</td>
        <td>{{ $endTrip->tol !== null ? number_format($endTrip->tol, 2) : '-' }}</td>
    </tr>
    <tr>
        <td class="label">Others (RM)</td>
        <td colspan="3">{{ $endTrip->others !== null ? number_format($endTrip->others, 2) : '-' }}</td>
    </tr>
</table>

{{-- Payment Summary --}}
<div class="section-label mt-10">Payment Summary</div>
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

{{-- Stock Movement --}}
<div class="section-label mt-10">Stock Movement</div>
<table class="stock-table">
    <thead>
        <tr>
            <th class="col-product" style="width:28%">Product</th>
            <th style="width:12%">Opening Stock</th>
            <th style="width:10%">Admin In<br><span style="font-weight:normal;font-size:9px;">(Assigned)</span></th>
            <th style="width:10%">Admin Out<br><span style="font-weight:normal;font-size:9px;">(Removed)</span></th>
            <th style="width:12%">Sales Used<br><span style="font-weight:normal;font-size:9px;">(Invoiced)</span></th>
            <th style="width:10%">Wastage<br><span style="font-weight:normal;font-size:9px;">(Written Off)</span></th>
            <th style="width:12%">Closing Stock</th>
            <th style="width:6%">Variance</th>
        </tr>
    </thead>
    <tbody>
        @forelse($stockMovements as $row)
        @php
            $variance = $row['closing_stock'] - ($row['opening_stock'] + $row['admin_in'] - $row['admin_out'] - $row['sales_used'] - $row['wastage']);
        @endphp
        <tr>
            <td class="col-product">{{ $row['product_name'] }}</td>
            <td>{{ $row['opening_stock'] }}</td>
            <td class="{{ $row['admin_in'] > 0 ? 'pos' : '' }}">{{ $row['admin_in'] > 0 ? '+' : '' }}{{ $row['admin_in'] }}</td>
            <td class="{{ $row['admin_out'] > 0 ? 'neg' : '' }}">{{ $row['admin_out'] > 0 ? '-' : '' }}{{ $row['admin_out'] }}</td>
            <td class="{{ $row['sales_used'] > 0 ? 'neg' : '' }}">{{ $row['sales_used'] > 0 ? '-' : '' }}{{ $row['sales_used'] }}</td>
            <td class="{{ $row['wastage'] > 0 ? 'neg' : '' }}">{{ $row['wastage'] > 0 ? '-' : '' }}{{ $row['wastage'] }}</td>
            <td style="font-weight:bold;">{{ $row['closing_stock'] }}</td>
            <td class="{{ $variance != 0 ? 'neg' : '' }}">{{ $variance != 0 ? ($variance > 0 ? '+' : '') . $variance : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:10px;color:#999;">No stock snapshot available for this trip.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Invoice Details --}}
<div class="section-label mt-10">Invoice Details</div>
<table class="invoice-table">
    <thead>
        <tr>
            <th style="width:12%">Invoice No</th>
            <th style="width:18%">Customer</th>
            <th style="width:12%">Payment</th>
            <th style="width:30%">Item</th>
            <th style="width:8%">Qty</th>
            <th style="width:10%">Price (RM)</th>
            <th style="width:10%">Amount (RM)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $invoice)
            @php
                $invoiceTotal = $invoice->invoicedetail->sum('totalprice');
                $paymentLabel = $paymentLabels[$invoice->paymentterm] ?? $invoice->paymentterm;
                $firstItem = true;
            @endphp
            @foreach($invoice->invoicedetail as $detail)
            <tr class="item-row">
                @if($firstItem)
                <td rowspan="{{ $invoice->invoicedetail->count() }}">{{ $invoice->invoiceno }}</td>
                <td rowspan="{{ $invoice->invoicedetail->count() }}">{{ $invoice->customer?->company ?? '-' }}</td>
                <td rowspan="{{ $invoice->invoicedetail->count() }}">{{ $paymentLabel }}</td>
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
            @endforeach
            <tr class="inv-total">
                <td colspan="6" style="text-align:right">Invoice Total:</td>
                <td class="text-right">{{ number_format($invoiceTotal, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;padding:12px;">No invoices found for this trip.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    This report was generated automatically &mdash; {{ now()->format('d-m-Y H:i:s') }}
</div>

</body>
</html>
