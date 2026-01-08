@extends('app')

@section('title', 'Server Error')
@section('content')
<div class="container text-center py-5">
    <h1 class="display-4 text-danger">500</h1>
    <h2 class="mb-4">Server Error</h2>
    <p class="lead">An unexpected error occurred. Please try again later.<br>If the problem persists, contact support.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return Home</a>
</div>
@endsection
