<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }} Email</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; }
        .container { background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px #eee; }
        .footer { margin-top: 32px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="container">
        {!! $bodyText !!}
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
