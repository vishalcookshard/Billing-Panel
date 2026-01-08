@component('mail::message')
# Support Ticket Created

Hello {{ $user->name }},

Your ticket #{{ $ticket->id }} has been created. Our support team will respond soon.

Thanks,<br>
{{ config('app.name') }}
@endcomponent