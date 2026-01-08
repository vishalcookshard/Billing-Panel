@component('mail::message')
# Customer Replied to Ticket

Hello {{ $user->name }},

A customer has replied to ticket #{{ $ticket->id }}.

Thanks,<br>
{{ config('app.name') }}
@endcomponent