@component('mail::message')
# Service Activated

Hello {{ $user->name }},

Your service {{ $service->name }} is now active. You can login here: [Control Panel]({{ $service->login_url }})

Thanks,<br>
{{ config('app.name') }}
@endcomponent