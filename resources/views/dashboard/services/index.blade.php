@extends('layouts.dashboard')
@section('content')
<h2>My Services</h2>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Plan</th>
            <th>Next Due</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($services as $service)
        <tr>
            <td>{{ $service->id }}</td>
            <td>{{ $service->name }}</td>
            <td>{{ ucfirst($service->status) }}</td>
            <td>{{ $service->plan->name ?? '' }}</td>
            <td>{{ $service->next_due_date ? $service->next_due_date->format('Y-m-d') : '-' }}</td>
            <td><a href="{{ route('services.show', $service->id) }}" class="btn btn-sm btn-primary">View</a></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection