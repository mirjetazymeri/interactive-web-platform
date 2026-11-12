@extends('emails.layout')

@section('subject') [Admin] Low Stock Alert ({{ $items->count() }} product(s)) @endsection

@section('body')
    <h2>Low Stock Alert</h2>

    <p>The following {{ $items->count() }} product(s) have fallen below the low-stock threshold:</p>

    <table class="data">
        <tr>
            <th>Product Name</th>
            <th>SKU</th>
            <th style="text-align:center;">Qty On Hand</th>
        </tr>
        @foreach($items as $item)
        <tr>
            <td>{{ $item['name'] }}</td>
            <td style="font-size:12px;">{{ $item['sku'] }}</td>
            <td style="text-align:center;">
                <span class="badge" style="background:#fff0f0; color:#e53e3e;">{{ $item['qty'] }}</span>
            </td>
        </tr>
        @endforeach
    </table>

    <p>Please restock these products to avoid order fulfilment issues.</p>
@endsection
