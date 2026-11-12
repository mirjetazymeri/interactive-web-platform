@extends('emails.layout')

@section('subject') [Admin] New Order #{{ $order->number }} @endsection

@section('body')
    <h2>New Order Received</h2>

    <p>A new order has been placed. Details below:</p>

    <table class="data">
        <tr><th>Order #</th><td><strong>{{ $order->number }}</strong></td></tr>
        <tr><th>Customer</th><td>{{ $order->user?->name ?? 'Guest' }}</td></tr>
        <tr><th>Email</th><td>{{ $order->email }}</td></tr>
        <tr><th>Status</th><td><span class="badge">{{ ucfirst($order->status) }}</span></td></tr>
        <tr><th>Placed At</th><td>{{ $order->placed_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i') }}</td></tr>
        <tr><th>Currency</th><td>{{ strtoupper($order->currency) }}</td></tr>
    </table>

    <h3 style="font-size:15px; margin-top:24px;">Order Items</h3>
    <table class="data">
        <tr>
            <th>Product</th>
            <th>SKU</th>
            <th style="text-align:center;">Qty</th>
            <th style="text-align:right;">Unit Price</th>
            <th style="text-align:right;">Total</th>
        </tr>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td style="font-size:12px;">{{ $item->sku }}</td>
            <td style="text-align:center;">{{ $item->qty }}</td>
            <td style="text-align:right;">{{ number_format($item->unit_price, 2) }}</td>
            <td style="text-align:right;">{{ number_format($item->row_total, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <table class="data" style="width:50%; margin-left:auto;">
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
            <td style="text-align:right;"><span class="amount">{{ strtoupper($order->currency) }} {{ number_format($order->grand_total, 2) }}</span></td>
        </tr>
    </table>
@endsection
