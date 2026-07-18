<table>
    <tbody>
        @forelse($blocks as $block)
            <tr>
                <td colspan="5" style="font-weight:bold;font-size:14px;">Lorry: {{ $block['lorry'] }}</td>
            </tr>
            <tr>
                <td colspan="5">Date: {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold;background-color:#E8E8E8;">Product Code</td>
                <td style="font-weight:bold;background-color:#E8E8E8;">Product Name</td>
                <td style="font-weight:bold;background-color:#E8E8E8;">Qty</td>
                <td style="font-weight:bold;background-color:#E8E8E8;">Unit Price (RM)</td>
                <td style="font-weight:bold;background-color:#E8E8E8;">Total Sales (RM)</td>
            </tr>
            @foreach($block['products'] as $p)
                <tr>
                    <td>{{ $p['code'] }}</td>
                    <td>{{ $p['name'] }}</td>
                    <td>{{ $p['qty'] }}</td>
                    <td>{{ $p['unit_price'] }}</td>
                    <td>{{ $p['total'] }}</td>
                </tr>
            @endforeach
            <tr style="font-weight:bold;">
                <td colspan="2">{{ $block['lorry'] }} - TOTAL</td>
                <td>{{ $block['qty'] }}</td>
                <td></td>
                <td>{{ $block['total'] }}</td>
            </tr>
            <tr>
                <td colspan="5"></td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No sales data found for this period.</td>
            </tr>
        @endforelse

        @if(count($blocks))
            <tr style="font-weight:bold;background-color:#E8E8E8;">
                <td colspan="2">GRAND TOTAL</td>
                <td>{{ $grandQty }}</td>
                <td></td>
                <td>{{ $grandTotal }}</td>
            </tr>
        @endif
    </tbody>
</table>
