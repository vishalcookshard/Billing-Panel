@component('mail::message')
# Service Suspended

Hello {{ $user->name }},

Your service {{ $service->name }} has been suspended. Please contact support for details.

Thanks,<br>
{{ config('app.name') }}
@endcomponent