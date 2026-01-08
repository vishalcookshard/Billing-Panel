@extends('layouts.admin')
@section('content')
<h2>Edit Department</h2>
<form method="POST" action="{{ route('admin.departments.update', $department->id) }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="{{ $department->name }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
</form>
@endsection