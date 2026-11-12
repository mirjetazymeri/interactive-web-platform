<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoiceNumber }}</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        /* Page container */
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 40px;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        .header .brand {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header .brand h1 {
            font-size: 26px;
            color: #2563eb;
            margin: 0 0 4px;
        }
        .header .brand .store-details {
            font-size: 11px;
            color: #666;
        }
        .header .invoice-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .header .invoice-info h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .header .invoice-info table {
            margin-left: auto;
        }
        .header .invoice-info td {
            padding: 2px 0;
        }
        .header .invoice-info td:first-child {
            color: #666;
            padding-right: 12px;
            text-align: right;
        }
        .header .invoice-info td:last-child {
            font-weight: 600;
            text-align: left;
        }

        /* Addresses */
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .address-block {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .address-block h3 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2563eb;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        .address-block p {
            font-size: 12px;
            line-height: 1.6;
            color: #444;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table thead th {
            background: #2563eb;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
        }
        .items-table thead th.right {
            text-align: right;
        }
        .items-table thead th.center {
            text-align: center;
        }
        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .items-table tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .items-table tbody td.right {
            text-align: right;
        }
        .items-table tbody td.center {
            text-align: center;
        }
        .items-table .sku {
            display: block;
            font-size: 10px;
            color: #999;
        }

        /* Totals */
        .totals-wrapper {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .totals-spacer {
            display: table-cell;
            width: 55%;
        }
        .totals-table-wrap {
            display: table-cell;
            width: 45%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table tr td {
            padding: 6px 12px;
            font-size: 12px;
        }
        .totals-table tr td:first-child {
            color: #666;
            text-align: right;
        }
        .totals-table tr td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .totals-table tr.grand-total {
            border-top: 2px solid #2563eb;
        }
        .totals-table tr.grand-total td {
            font-size: 14px;
            font-weight: 700;
            color: #2563eb;
            padding-top: 10px;
        }

        /* Payment info */
        .payment-info {
            background: #f3f4f6;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 11px;
        }
        .payment-info strong {
            color: #333;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        .footer .thank-you {
            font-size: 14px;
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 6px;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
            color: #fff;
        }
        .status-paid      { background: #16a34a; }
        .status-completed  { background: #16a34a; }
        .status-pending    { background: #9ca3af; }
        .status-processing { background: #06b6d4; }
        .status-shipped    { background: #2563eb; }
        .status-canceled   { background: #374151; }
        .status-refunded   { background: #f59e0b; }
    </style>
</head>
<body>
<div class="invoice-box">

    {{-- ─── HEADER ───────────────────────────────────────── --}}
    <div class="header">
        <div class="brand">
            @if(!empty($store['logo']))
                <img src="{{ public_path('storage/' . $store['logo']) }}" alt="Logo" style="max-height: 50px; margin-bottom: 6px;">
            @endif
            <h1>{{ $store['name'] }}</h1>
            <div class="store-details">
                @if(!empty($store['address'])){{ $store['address'] }}<br>@endif
                @if(!empty($store['city']) || !empty($store['postcode']))
                    {{ $store['postcode'] }} {{ $store['city'] }}
                    @if(!empty($store['country'])), {{ strtoupper($store['country']) }}@endif
                    <br>
                @endif
                @if(!empty($store['phone']))Tel: {{ $store['phone'] }}<br>@endif
                @if(!empty($store['email'])){{ $store['email'] }}<br>@endif
                @if(!empty($store['vat']))VAT: {{ $store['vat'] }}<br>@endif
                @if(!empty($store['reg_number']))Reg. No: {{ $store['reg_number'] }}@endif
            </div>
        </div>
        <div class="invoice-info">
            <h2>Invoice</h2>
            <table>
                <tr><td>Invoice #:</td><td>{{ $invoiceNumber }}</td></tr>
                <tr><td>Order #:</td><td>{{ $order->number }}</td></tr>
                <tr><td>Date:</td><td>{{ $invoiceDate->format('M d, Y') }}</td></tr>
                <tr>
                    <td>Status:</td>
                    <td><span class="status-badge status-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ─── ADDRESSES ────────────────────────────────────── --}}
    <div class="addresses">
        <div class="address-block">
            <h3>Bill To</h3>
            <p>
                {{ ($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '') }}<br>
                @if(!empty($billing['company'])){{ $billing['company'] }}<br>@endif
                {{ $billing['address1'] ?? '' }}<br>
                @if(!empty($billing['address2'])){{ $billing['address2'] }}<br>@endif
                {{ ($billing['postcode'] ?? '') . ' ' . ($billing['city'] ?? '') }}<br>
                {{ strtoupper($billing['country'] ?? '') }}<br>
                @if(!empty($billing['phone']))Tel: {{ $billing['phone'] }}<br>@endif
                @if(!empty($billing['vat']))VAT: {{ $billing['vat'] }}@endif
            </p>
        </div>
        <div class="address-block" style="padding-left: 30px;">
            <h3>Ship To</h3>
            <p>
                {{ ($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '') }}<br>
                @if(!empty($shipping['company'])){{ $shipping['company'] }}<br>@endif
                {{ $shipping['address1'] ?? '' }}<br>
                @if(!empty($shipping['address2'])){{ $shipping['address2'] }}<br>@endif
                {{ ($shipping['postcode'] ?? '') . ' ' . ($shipping['city'] ?? '') }}<br>
                {{ strtoupper($shipping['country'] ?? '') }}<br>
                @if(!empty($shipping['phone']))Tel: {{ $shipping['phone'] }}@endif
            </p>
        </div>
    </div>

    {{-- ─── ITEMS TABLE ──────────────────────────────────── --}}
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 42%;">Description</th>
            <th class="center" style="width: 10%;">Qty</th>
            <th class="right" style="width: 13%;">Price</th>
            <th class="right" style="width: 13%;">Tax</th>
            <th class="right" style="width: 17%;">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                {{ $item->name }}
                @if($item->sku)<span class="sku">SKU: {{ $item->sku }}</span>@endif
            </td>
            <td class="center">{{ $item->qty }}</td>
            <td class="right">{{ number_format($item->unit_price, 2) }}</td>
            <td class="right">{{ number_format($item->tax, 2) }}</td>
            <td class="right">{{ number_format($item->row_total, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

    {{-- ─── TOTALS ───────────────────────────────────────── --}}
    <div class="totals-wrapper">
        <div class="totals-spacer">
            {{-- Payment info --}}
            @if($order->payments->isNotEmpty())
            <div class="payment-info">
                <strong>Payment:</strong>
                @foreach($order->payments as $p)
                    {{ $p->method_name ?? $p->method_code }}
                    @if(!$loop->last) / @endif
                @endforeach
                @if($order->paid_at)
                    &mdash; Paid on {{ $order->paid_at->format('M d, Y') }}
                @endif
            </div>
            @endif
        </div>
        <div class="totals-table-wrap">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td>{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</td>
                </tr>
                @if($order->discount_total > 0)
                <tr>
                    <td>Discount{{ $order->coupon_code ? " ({$order->coupon_code})" : '' }}:</td>
                    <td>-{{ number_format($order->discount_total, 2) }} {{ $order->currency }}</td>
                </tr>
                @endif
                @if($order->shipping_total > 0)
                <tr>
                    <td>Shipping:</td>
                    <td>{{ number_format($order->shipping_total, 2) }} {{ $order->currency }}</td>
                </tr>
                @endif
                @if($order->tax_total > 0)
                <tr>
                    <td>Tax:</td>
                    <td>{{ number_format($order->tax_total, 2) }} {{ $order->currency }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td>Grand Total:</td>
                    <td>{{ number_format($order->grand_total, 2) }} {{ $order->currency }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ─── NOTES ────────────────────────────────────────── --}}
    @if($order->customer_note)
    <div class="payment-info">
        <strong>Customer Note:</strong> {{ $order->customer_note }}
    </div>
    @endif

    {{-- ─── FOOTER ───────────────────────────────────────── --}}
    <div class="footer">
        <div class="thank-you">{{ $store['footer_note'] }}</div>
        <p>
            {{ $store['name'] }}
            @if(!empty($store['vat'])) &mdash; VAT: {{ $store['vat'] }} @endif
            @if(!empty($store['reg_number'])) &mdash; Reg: {{ $store['reg_number'] }} @endif
        </p>
        @if(!empty($store['bank_name']) || !empty($store['bank_iban']))
        <p>
            @if(!empty($store['bank_name']))Bank: {{ $store['bank_name'] }} @endif
            @if(!empty($store['bank_iban']))&mdash; IBAN: {{ $store['bank_iban'] }} @endif
            @if(!empty($store['bank_swift']))&mdash; SWIFT: {{ $store['bank_swift'] }} @endif
        </p>
        @endif
        <p style="margin-top: 6px;">Generated on {{ now()->format('M d, Y H:i') }}</p>
    </div>

</div>
</body>
</html>
