@extends('emails.layout')

@section('subject') You left something behind — complete your order @endsection

@section('body')
    <h2>You left something in your cart!</h2>

    <p>Hi {{ $recipientName }},</p>

    <p>
        You have items waiting in your cart. Don't miss out — complete your purchase before they sell out!
    </p>

    <h3 style="font-size:15px; margin-top:20px;">Your Cart</h3>
    <table class="data">
        <tr>
            <th>Product</th>
            <th style="text-align:center;">Qty</th>
            <th style="text-align:right;">Price</th>
        </tr>
        @foreach($cart->items as $item)
        <tr>
            <td>{{ $item->name ?? $item->product?->name ?? 'Product' }}</td>
            <td style="text-align:center;">{{ $item->qty }}</td>
            <td style="text-align:right;">{{ number_format($item->price ?? $item->unit_price ?? 0, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <table class="data" style="width:50%; margin-left:auto; margin-top:8px;">
        <tr>
            <th>Cart Total</th>
            <td style="text-align:right;">
                <span class="amount">{{ strtoupper($cart->currency) }} {{ number_format($cart->grand_total, 2) }}</span>
            </td>
        </tr>
    </table>

    <p style="margin-top:24px; text-align:center;">
        <a class="btn" href="{{ $checkoutUrl }}">Complete My Order</a>
    </p>

    <hr class="divider">
    <p style="font-size:13px; color:#999;">
        Your cart will be saved for a limited time.
        If you no longer wish to receive cart reminders,
        <a href="{{ config('app.url') }}/account/settings">update your preferences</a>.
    </p>
@endsection
