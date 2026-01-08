@extends('layouts.dashboard')
@section('content')
<h2>Create Ticket</h2>
<form method="POST" action="{{ route('tickets.store') }}">
    @csrf
    <div class="mb-3">
        <label>Department</label>
        <select name="department_id" class="form-control">
            @foreach($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label>Subject</label>
        <input type="text" name="subject" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Priority</label>
        <select name="priority" class="form-control">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Message</label>
        <textarea name="message" class="form-control" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
@endsection