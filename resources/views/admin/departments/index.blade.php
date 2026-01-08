@extends('layouts.admin')
@section('content')
<h2>Departments</h2>
<a href="{{ route('admin.departments.create') }}" class="btn btn-success mb-3">Add Department</a>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($departments as $department)
        <tr>
            <td>{{ $department->id }}</td>
            <td>{{ $department->name }}</td>
            <td>
                <a href="{{ route('admin.departments.edit', $department->id) }}" class="btn btn-sm btn-primary">Edit</a>
                <form method="POST" action="{{ route('admin.departments.destroy', $department->id) }}" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection