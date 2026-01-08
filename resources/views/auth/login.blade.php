@extends('layouts.app')

@section('content')
<h1>Login</h1>
<form method="POST" action="{{ route('login') }}" id="login-form">
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
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control">
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password" name="password" required class="form-control">
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" name="remember" id="remember">
        <label class="form-check-label" for="remember">Remember me</label>
    </div>

    <div class="mb-3">
        <button type="submit" class="btn btn-primary w-100" id="login-btn">Login</button>
    </div>

    <div class="text-center">
        <a href="{{ route('password.request') }}">Forgot your password?</a>
    </div>
</form>

<script>
    document.getElementById('login-form').addEventListener('submit', function(e) {
        var btn = document.getElementById('login-btn');
        btn.disabled = true;
        btn.innerHTML = 'Logging in...';
    });
</script>
@endsection