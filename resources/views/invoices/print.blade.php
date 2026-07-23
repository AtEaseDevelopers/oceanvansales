<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <style>
        @page {
            margin-bottom:30px;
            margin-top:30px;
            margin-left:30px;
            margin-right:30px;
        }
        body{
            font-size: 14px;
            margin: 0%;
            font-family: Arial, Helvetica, sans-serif;
        }
        table{
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table th, table td{
            /* border: 1px solid black; */
            font-size: 12px;
        }

        .login-image{
            width: auto;
            height: 55px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            margin-bottom: 0.5rem;
        }
        .company{
            font-weight: bold;
            text-align: center;
        }
        .address{
            text-align: center;
        }
        p{
            margin: 0%;
        }
        .ta-r{
            text-align: right;
        }
        .ta-l{
            text-align: left;
        }
        .paidsummary{
            text-align: center;
            font-weight: bold;
            color: #394068;
        }
    </style>
</head>
<body>
    <table class="invoice">
      
        <tr>
            <td>
                <p class="company">{{ $company->name ?? '-' }}</p>
            </td>
        </tr>
        @if(!empty($company->ssm))
        <tr>
            <td>
                <p class="address">({{ $company->ssm }})</p>
            </td>
        </tr>
        @endif
        @if(!empty($company->tin))
        <tr>
            <td>
                <p class="address">({{ $company->tin }})</p>
            </td>
        </tr>
        @endif
        @if(!empty($company->address1))
        <tr>
            <td>
                <p class="address">{{ $company->address1 }}</p>
            </td>
        </tr>
        @endif
        @if(!empty($company->address2))
        <tr>
            <td>
                <p class="address">{{ $company->address2 }}</p>
            </td>
        </tr>
        @endif
        @if(!empty($company->address3))
        <tr>
            <td>
                <p class="address">{{ $company->address3 }}</p>
            </td>
        </tr>
        @endif
        @if(!empty($company->address4))
        <tr>
            <td>
                <p class="address">{{ $company->address4 }}</p>
            </td>
        </tr>
        @endif
       
        <tr>
            <td>
                <br>
                <table id="header">
                    <tr>
                        <td width="35%">
                            <p>Invoice</p>
                        </td>
                        <td width="65%">
                            <p class="ta-r">{{ $invoice['invoiceno'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Invoice Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['date'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Payment Method</p>
                        </td>
                        <td>
                            <p class="ta-r">
                                {{ \App\Models\Customer::PAYMENT_TERMS[$invoice['paymentterm']] ?? $invoice['paymentterm'] }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Address</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['customer']['address'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Driver</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['driver']['name'] ?? '-' }}</p>
                        </td>
                    </tr>
                    
                    <tr><td height="15">&nbsp;</td></tr>
                    <tr>
                        <td>
                            <p style="font-size:16px; font-weight:bold;">Customer</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px; font-weight:bold;">{{ $invoice['customer']['company'] ?? '-' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="detail">
                    <tr>
                        <th>
                            <p class="ta-l">Product</p>
                        </th>
                        <th>
                            <p class="ta-r">Price <br>(RM)</p>
                        </th>
                        <th>
                            <p class="ta-r">Qty</p>
                        </th>
                        <th>
                            <p class="ta-r">Subtotal</p>
                        </th>
                    </tr>
                    @php
                            $totalamount = 0;
                    @endphp
                    @foreach ($invoice['invoicedetail'] as $invoicedetail)
                        @php
                            $totalamount = ($totalamount ?? 0) + $invoicedetail['totalprice'];
                        @endphp
                        <tr>
                            <td>
                                <p style="font-size:16px;">{{ $invoicedetail['product']['name'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($invoicedetail['price'],2) }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ $invoicedetail['quantity'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($invoicedetail['totalprice'],2) }}</p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="total">
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Total</p>
                        </th>
                        <th>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($totalamount,2) }}</p>
                        </td>
                    </tr>
                </table>
                <p class="paidsummary">Paid Summary</p>
                <table id="footer">
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Paid Amount</p>
                        </th>
                        <td>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($totalamount,2) }}</p>
                        </td>
                    </tr>
                </table>
                <br>
                <br>
                <br>
                <br>
                <br>
                <hr style="border: none; border-top: 1px solid black;">
            </td>
        </tr>
    </table>
</body>

</html>
