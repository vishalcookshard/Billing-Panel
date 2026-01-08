@component('mail::message')
# Staff Replied to Ticket

Hello {{ $user->name }},

A staff member has replied to your ticket #{{ $ticket->id }}.

Thanks,<br>
{{ config('app.name') }}
@endcomponent