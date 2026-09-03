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
