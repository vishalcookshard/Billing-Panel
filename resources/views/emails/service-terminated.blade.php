@component('mail::message')
# Service Terminated

Hello {{ $user->name }},

Your service {{ $service->name }} has been terminated.

Thanks,<br>
{{ config('app.name') }}
@endcomponent