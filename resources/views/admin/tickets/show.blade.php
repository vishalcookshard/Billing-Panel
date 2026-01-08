@extends('layouts.admin')
@section('content')
<h2>Ticket #{{ $ticket->id }} - {{ $ticket->subject }}</h2>
<p>User: <strong>{{ $ticket->user->name }}</strong></p>
<p>Status: <strong>{{ ucfirst($ticket->status) }}</strong></p>
<p>Priority: <strong>{{ ucfirst($ticket->priority) }}</strong></p>
<p>Department: <strong>{{ $ticket->department->name }}</strong></p>
<hr>
@foreach($ticket->replies as $reply)
    <div class="mb-3">
        <strong>{{ $reply->user->name }}</strong> <span class="text-muted">{{ $reply->created_at->format('Y-m-d H:i') }}</span>
        <div>{{ $reply->message }}</div>
    </div>
@endforeach
<hr>
@if($ticket->status !== 'closed')
<form method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}">
    @csrf
    <div class="mb-3">
        <label>Reply</label>
        <textarea name="message" class="form-control" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Send Reply</button>
</form>
<form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}" class="mt-2">
    @csrf
    <button type="submit" class="btn btn-danger">Close Ticket</button>
</form>
@endif
@endsection