@extends('app')

@section('title', 'Not Found')
@section('content')
<div class="container text-center py-5">
    <h1 class="display-4 text-secondary">404</h1>
    <h2 class="mb-4">Page Not Found</h2>
    <p class="lead">Sorry, the page you are looking for could not be found.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return Home</a>
</div>
@endsection
