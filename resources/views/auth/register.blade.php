@extends('layouts.app')

@section('content')
<h1>Register</h1>
<form method="POST" action="{{ route('register') }}" id="register-form">
    @csrf

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="form-control">
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-control">
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password" name="password" required class="form-control">
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required class="form-control">
    </div>

    <div class="mb-3">
        <button type="submit" class="btn btn-primary w-100" id="register-btn">Register</button>
    </div>
</form>

<script>
    document.getElementById('register-form').addEventListener('submit', function(e) {
        var btn = document.getElementById('register-btn');
        btn.disabled = true;
        btn.innerHTML = 'Registering...';
    });
</script>
@endsection