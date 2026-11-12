@extends('emails.layout')

@section('subject') [Admin] New Product Review @endsection

@section('body')
    <h2>New Product Review Submitted</h2>

    <p>A customer has submitted a review that may need moderation.</p>

    <table class="data">
        <tr><th>Product</th><td>{{ $review['product_name'] }}</td></tr>
        <tr><th>Reviewer</th><td>{{ $review['reviewer_name'] }}</td></tr>
        <tr>
            <th>Rating</th>
            <td>
                @for($i = 1; $i <= 5; $i++)
                    {{ $i <= $review['rating'] ? '★' : '☆' }}
                @endfor
                ({{ $review['rating'] }}/5)
            </td>
        </tr>
        <tr><th>Review</th><td>{{ $review['body'] }}</td></tr>
    </table>
@endsection
