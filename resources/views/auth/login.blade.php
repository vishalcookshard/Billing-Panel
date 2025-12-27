@extends('layouts.app')

@section('content')
<h1>Login</h1>
<form method="POST" action="{{ route('login') }}">
    @csrf

    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>

    <div>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
    </div>

    <div>
        <label>
            <input type="checkbox" name="remember"> Remember me
        </label>
    </div>

    <div>
        <button type="submit">Login</button>
    </div>

    <div>
        <a href="{{ route('password.request') }}">Forgot your password?</a>
    </div>
</form>
@endsection