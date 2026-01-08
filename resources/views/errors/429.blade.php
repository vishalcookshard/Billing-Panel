@extends('app')

@section('title', 'Too Many Requests')
@section('content')
<div class="container text-center py-5">
    <h1 class="display-4 text-warning">429</h1>
    <h2 class="mb-4">Too Many Requests</h2>
    <p class="lead">You have made too many requests in a short period.<br>Please wait a moment and try again.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return Home</a>
</div>
@endsection
