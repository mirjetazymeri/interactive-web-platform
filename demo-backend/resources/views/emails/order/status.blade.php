@extends('emails.layout')

@section('subject') Order #{{ $order->number }} — {{ ucfirst($order->status) }} @endsection

@section('body')
@php
    $messages = [
        'pending'    => ['Your order has been received and is awaiting confirmation.',  '#888'],
        'processing' => ['Great! Your order is now being processed.',                  '#1a56db'],
        'paid'       => ['Your payment has been confirmed. Thank you!',                '#38a169'],
        'shipped'    => ['Your order is on its way!',                                  '#2b6cb0'],
        'completed'  => ['Your order has been delivered. Enjoy!',                      '#38a169'],
        'canceled'   => ['Your order has been cancelled.',                             '#e53e3e'],
        'refunded'   => ['A refund has been issued for your order.',                   '#dd6b20'],
    ];
    [$statusMsg, $statusColor] = $messages[$order->status] ?? ['Your order status has been updated.', '#888'];
@endphp

    <h2 style="color:{{ $statusColor }};">
        Order {{ ucfirst($order->status) }}
    </h2>

    <div class="highlight" style="border-color:{{ $statusColor }};">
        {{ $statusMsg }}
    </div>

    @if($order->status === 'shipped' && $trackingNumber)
    <div class="highlight" style="border-color:#2b6cb0; margin-top:12px;">
        <strong>Tracking Number:</strong> {{ $trackingNumber }}
    </div>
    @endif

    <table class="data" style="margin-top:20px;">
        <tr><th>Order #</th><td><strong>{{ $order->number }}</strong></td></tr>
        <tr><th>Status</th><td><span class="badge">{{ ucfirst($order->status) }}</span></td></tr>
        <tr><th>Date</th><td>{{ $order->placed_at?->format('d M Y') ?? now()->format('d M Y') }}</td></tr>
        <tr><th>Currency</th><td>{{ strtoupper($order->currency) }}</td></tr>
    </table>

    <h3 style="font-size:15px; margin-top:24px;">Items Ordered</h3>
    <table class="data">
        <tr>
            <th>Product</th>
            <th>SKU</th>
            <th style="text-align:center;">Qty</th>
            <th style="text-align:right;">Unit Price</th>
            <th style="text-align:right;">Total</th>
        </tr>
        @foreach($lineItems as $item)
        <tr>
            <td>{{ $item['name'] }}</td>
            <td style="font-size:12px;">{{ $item['sku'] }}</td>
            <td style="text-align:center;">{{ $item['qty'] }}</td>
            <td style="text-align:right;">{{ $item['price'] }}</td>
            <td style="text-align:right;">{{ $item['total'] }}</td>
        </tr>
        @endforeach
    </table>

    <table class="data" style="width:50%; margin-left:auto; margin-top:8px;">
        <tr><th>Subtotal</th><td style="text-align:right;">{{ number_format($order->subtotal, 2) }}</td></tr>
        @if($order->discount_total > 0)
        <tr><th>Discount</th><td style="text-align:right; color:#e53e3e;">-{{ number_format($order->discount_total, 2) }}</td></tr>
        @endif
        @if($order->shipping_total > 0)
        <tr><th>Shipping</th><td style="text-align:right;">{{ number_format($order->shipping_total, 2) }}</td></tr>
        @endif
        @if($order->tax_total > 0)
        <tr><th>Tax</th><td style="text-align:right;">{{ number_format($order->tax_total, 2) }}</td></tr>
        @endif
        <tr>
            <th>Grand Total</th>
            <td style="text-align:right;">
                <span class="amount">{{ strtoupper($order->currency) }} {{ number_format($order->grand_total, 2) }}</span>
            </td>
        </tr>
    </table>

    @if($order->shippingAddress)
    @php
        $addr = $order->shippingAddress;
        $d    = $addr->data ?? [];
    @endphp
    <h3 style="font-size:15px; margin-top:24px;">Shipping Address</h3>
    <div class="highlight" style="line-height:1.8;">
        {{ ($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '') }}<br>
        {{ $d['address1'] ?? '' }}@if(!empty($d['address2'])), {{ $d['address2'] }}@endif<br>
        {{ $d['city'] ?? '' }}@if(!empty($d['state'])), {{ $d['state'] }}@endif {{ $d['postcode'] ?? '' }}<br>
        {{ $d['country'] ?? '' }}
    </div>
    @endif
@endsection
