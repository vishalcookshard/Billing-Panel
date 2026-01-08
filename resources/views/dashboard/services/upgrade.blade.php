@extends('layouts.dashboard')
@section('content')
<h2>Upgrade Service</h2>
<form method="POST" action="{{ route('services.upgrade', $service->id) }}">
    @csrf
    <div class="mb-3">
        <label>New Plan</label>
        <select name="new_plan_id" class="form-control">
            {{-- List available plans --}}
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Request Upgrade</button>
</form>
@endsection