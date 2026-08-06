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
                            <p>Delivery Order</p>
                        </td>
                        <td width="65%">
                            <p class="ta-r">{{ $deliveryOrder['invoiceno'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $deliveryOrder['date'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Payment Method</p>
                        </td>
                        <td>
                            <p class="ta-r">
                                {{ \App\Models\Customer::PAYMENT_TERMS[$deliveryOrder['paymentterm']] ?? $deliveryOrder['paymentterm'] }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Address</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $deliveryOrder['customer']['address'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Driver</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $deliveryOrder['driver']['name'] ?? '-' }}</p>
                        </td>
                    </tr>

                    <tr><td height="15">&nbsp;</td></tr>
                    <tr>
                        <td>
                            <p style="font-size:16px; font-weight:bold;">Customer</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px; font-weight:bold;">{{ $deliveryOrder['customer']['company'] ?? '-' }}</p>
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
                            <p class="ta-r">Qty</p>
                        </th>
                    </tr>
                    @php
                            $totalqty = 0;
                    @endphp
                    @foreach ($deliveryOrder['deliveryorderdetail'] as $deliveryorderdetail)
                        @php
                            $totalqty = ($totalqty ?? 0) + $deliveryorderdetail['quantity'];
                        @endphp
                        <tr>
                            <td>
                                <p style="font-size:16px;">{{ $deliveryorderdetail['product']['name'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ $deliveryorderdetail['quantity'] }}</p>
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
                            <p class="ta-l" style="font-size:18px;">Total Qty</p>
                        </th>
                        <th>
                            <p class="ta-r" style="font-size:18px;">{{ $totalqty }}</p>
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
