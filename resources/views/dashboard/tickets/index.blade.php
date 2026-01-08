@extends('layouts.dashboard')
@section('content')
<h2>My Tickets</h2>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Department</th>
            <th>Created</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $ticket)
        <tr>
            <td>{{ $ticket->id }}</td>
            <td>{{ $ticket->subject }}</td>
            <td>{{ ucfirst($ticket->status) }}</td>
            <td>{{ ucfirst($ticket->priority) }}</td>
            <td>{{ $ticket->department->name }}</td>
            <td>{{ $ticket->created_at->format('Y-m-d') }}</td>
            <td><a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-primary">View</a></td>
        </tr>
        @endforeach
    </tbody>
</table>
<a href="{{ route('tickets.create') }}" class="btn btn-success">Create Ticket</a>
@endsection