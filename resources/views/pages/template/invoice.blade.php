<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Számla #{{ $invoice_id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 40px;
            color: #333;
        }

        h1, h2 {
            margin: 0 0 5px;
        }

        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .invoice-meta {
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #444;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .right {
            text-align: right;
        }

        .summary {
            margin-top: 20px;
            width: 50%;
            float: right;
        }

        .clear {
            clear: both;
        }

        .footer {
            text-align: center;
            margin-top: 60px;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="invoice-title">Számla #{{ $invoice_id }}</div>

    <div class="invoice-meta">
        <p>
            Számla kelte: {{ $invoice_date }}<br>
            Teljesítés dátuma: {{ $fulfillment_date }}<br>
            Fizetési határidő: {{ $due_date }}<br><br>
            Rendelésszám: {{ $order_id }}
        </p>
    </div>

    <div class="section">
        <h2>Eladó</h2>
        <p>
            <strong>{{ $seller_name }}</strong><br>
            {{ $seller_address_line1 }}<br>
            {{ $seller_address_line2 }}<br>
            {{ $seller_country }}<br><br>
            Adószám: {{ $seller_tax_id }}<br>
            Cégjegyzékszám: {{ $seller_company_reg_id }}
        </p>
    </div>

    <div class="section">
        <h2>Vevő</h2>
        <p>
            <strong>{{ $buyer_name }}</strong><br>
            {{ $buyer_address_line1 }}<br>
            {{ $buyer_address_line2 }}<br>
            {{ $buyer_city_zip }}, {{ $region }}
        </p>
    </div>

    <div class="section">
        <h2>Tételek</h2>
        <table>
            <thead>
                <tr>
                    <th>Tétel</th>
                    <th>Mennyiség</th>
                    <th class="right">Egységár (nettó)</th>
                    <th class="right">Összeg (bruttó)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    @if($item['name'] !== 'Delivery fee')
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td class="right">{{ $item['quantity'] }}</td>
                            <td class="right">{{ $item['unit_price_net'] }} Ft</td>
                            <td class="right">{{ $item['total_price_gross'] }} Ft</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td><strong>Összesen (nettó):</strong></td>
                <td class="right">{{ number_format($grand_total, 2, ',', ' ') }} Ft</td>
            </tr>
            <tr>
                <td><strong>Fizetendő végösszeg:</strong></td>
                <td class="right">{{ $grand_total }} Ft</td>
            </tr>
        </table>
    </div>

    <div class="clear"></div>

    <div class="footer">
        <p>{{ $footer_legal_text_1 }}</p>
        <p>{{ $footer_legal_text_2 }}</p>
    </div>
</body>
</html>
