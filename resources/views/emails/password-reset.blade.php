@component('mail::message')
# Password Reset Request

Hello {{ $user->name }},

Click the link below to reset your password:

[Reset Password]({{ $resetLink }})

Thanks,<br>
{{ config('app.name') }}
@endcomponent