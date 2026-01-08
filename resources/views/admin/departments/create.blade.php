@extends('layouts.admin')
@section('content')
<h2>Add Department</h2>
<form method="POST" action="{{ route('admin.departments.store') }}">
    @csrf
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Create</button>
</form>
@endsection