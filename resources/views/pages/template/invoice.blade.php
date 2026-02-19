<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Számla #{{ $invoice_id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.8;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 14px;
            color: #666;
        }

        /* Invoice Meta */
        .invoice-meta {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .meta-left, .meta-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
        }

        .info-box h3 {
            font-size: 12px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-box p {
            margin: 0;
            line-height: 1.8;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-label {
            display: inline-block;
            width: 140px;
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        /* Items Table */
        .items-section {
            margin: 30px 0;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #2c3e50;
            color: #fff;
        }

        th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        th.right {
            text-align: right;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        td {
            padding: 12px 10px;
            color: #333;
        }

        td.right {
            text-align: right;
            font-weight: 500;
        }

        /* Summary Section */
        .summary-section {
            margin-top: 30px;
            width: 350px;
            float: right;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 0;
        }

        .summary-table td {
            padding: 10px;
            border: none;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            color: #666;
            font-weight: 500;
        }

        .summary-value {
            text-align: right;
            font-weight: 600;
            color: #333;
        }

        .total-row {
            background: #2c3e50;
            color: #fff !important;
        }

        .total-row td {
            font-size: 14px;
            font-weight: bold;
            padding: 15px 10px !important;
            border-bottom: none !important;
        }

        .total-row .summary-label,
        .total-row .summary-value {
            color: #fff;
        }

        /* Footer */
        .clear {
            clear: both;
        }

        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #999;
            font-size: 9px;
            line-height: 1.8;
        }

        .footer p {
            margin-bottom: 5px;
        }

        /* Highlight boxes */
        .highlight-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin: 20px 0;
            font-size: 10px;
        }

        /* Payment details */
        .payment-info {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }

        .payment-info h4 {
            font-size: 12px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        /* Print optimization */
        @media print {
            .container {
                padding: 0;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $seller_name }}</div>
                <div class="company-details">
                    {{ $seller_address_line1 }}<br>
                    {{ $seller_address_line2 }}<br>
                    {{ $seller_country }}<br>
                    <strong>Adószám:</strong> {{ $seller_tax_id }}<br>
                    <strong>Cégjegyzékszám:</strong> {{ $seller_company_reg_id }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">SZÁMLA</div>
                <div class="invoice-number">#{{ $invoice_id }}</div>
            </div>
        </div>

        <!-- Invoice Meta Information -->
        <div class="invoice-meta">
            <div class="meta-left">
                <div class="info-box">
                    <h3>Számlázási információ</h3>
                    <div class="info-row">
                        <span class="info-label">Számla kelte:</span>
                        <span class="info-value">{{ $invoice_date }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Teljesítés dátuma:</span>
                        <span class="info-value">{{ $fulfillment_date }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fizetési határidő:</span>
                        <span class="info-value">{{ $due_date }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rendelésszám:</span>
                        <span class="info-value">{{ $order_id }}</span>
                    </div>
                </div>
            </div>
            <div class="meta-right">
                <div class="info-box">
                    <h3>Vevő adatai</h3>
                    <p>
                        <strong>{{ $buyer_name }}</strong><br>
                        {{ $buyer_address_line1 }}<br>
                        {{ $buyer_address_line2 }}<br>
                        {{ $buyer_city_zip }}, {{ $region }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="items-section">
            <div class="section-title">Számla tételek</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%;">Megnevezés</th>
                        <th style="width: 15%;" class="right">Mennyiség</th>
                        <th style="width: 17.5%;" class="right">Összeg (bruttó)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @if($item['name'] !== 'Delivery fee')
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="right">{{ $item['quantity'] }}</td>
                                <td class="right">{{ $item['total_price_gross'] }} Ft</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">Összesen:</td>
                    <td class="summary-value">{{ number_format($grand_total, 0, ',', ' ') }} Ft</td>
                </tr>

                @if($has_delivery_fee)
                    <tr>
                        <td class="summary-label">Szállítási díj:</td>
                        <td class="summary-value">{{ number_format(2500, 0, ',', ' ') }} Ft</td>
                    </tr>
                @endif
                <tr>
                    <td class="summary-label">ÁFA (23%):</td>
                    <td class="summary-value">{{ number_format($grand_total * 0.23, 0, ',', ' ') }} Ft</td>
                </tr>
                <tr class="total-row">
                    <td class="summary-label">Fizetendő végösszeg:</td>
                    <td class="summary-value">{{ number_format($grand_total, 0, ',', ' ') }} Ft</td>
                </tr>
            </table>
        </div>

        <div class="clear"></div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ $footer_legal_text_1 }}</p>
            <p>{{ $footer_legal_text_2 }}</p>
            <p style="margin-top: 15px;">Köszönjük megrendelését!</p>
        </div>
    </div>
</body>
</html>
