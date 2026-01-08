@extends('layouts.dashboard')
@section('content')
<h2>Service #{{ $service->id }} - {{ $service->name }}</h2>
<p>Status: <strong>{{ ucfirst($service->status) }}</strong></p>
<p>Plan: <strong>{{ $service->plan->name ?? '' }}</strong></p>
<p>Next Due: <strong>{{ $service->next_due_date ? $service->next_due_date->format('Y-m-d') : '-' }}</strong></p>
<p>Monthly Price: <strong>${{ number_format($service->monthly_price, 2) }}</strong></p>
<hr>
<a href="{{ route('services.login', $service->id) }}" class="btn btn-success">Login to Control Panel</a>
<a href="{{ route('services.upgrade', $service->id) }}" class="btn btn-warning">Upgrade</a>
<a href="{{ route('services.cancel', $service->id) }}" class="btn btn-danger">Cancel</a>
<a href="{{ route('services.addons', $service->id) }}" class="btn btn-info">Manage Addons</a>
<hr>
<div id="usage-widget"></div>
@endsection