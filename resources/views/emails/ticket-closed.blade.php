@component('mail::message')
# Ticket Closed

Hello {{ $user->name }},

Your ticket #{{ $ticket->id }} has been closed. If you need further assistance, please open a new ticket.

Thanks,<br>
{{ config('app.name') }}
@endcomponent