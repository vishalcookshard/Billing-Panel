<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'BillingPanel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        body { background: #f8f9fa; color: #222; font-family: system-ui, sans-serif; }
        .container { max-width: 480px; margin: 0 auto; padding: 2rem 1rem; }
        .alert { border-radius: 8px; }
        @media (max-width: 600px) {
            .container { padding: 1rem 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>