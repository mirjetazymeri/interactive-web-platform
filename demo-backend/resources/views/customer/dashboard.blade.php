@extends('layouts.master')

@section('content')
<div class="container mt-4">
    <h1>Customer Dashboard</h1>
    <p>Welcome to your dashboard, {{ auth()->user()->name }}!</p>
</div>
@endsection
