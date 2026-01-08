@extends('app')

@section('title', 'Page Expired')
@section('content')
<div class="container text-center py-5">
    <h1 class="display-4 text-danger">419</h1>
    <h2 class="mb-4">Page Expired</h2>
    <p class="lead">Your session has expired or the page was open too long.<br>Please refresh and try again.</p>
    <a href="{{ url()->previous() }}" class="btn btn-primary mt-3">Go Back</a>
</div>
@endsection
