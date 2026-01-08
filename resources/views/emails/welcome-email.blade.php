@component('mail::message')
# Welcome to {{ config('app.name') }}

Hello {{ $user->name }},

Thank you for joining our platform!

Thanks,<br>
{{ config('app.name') }}
@endcomponent