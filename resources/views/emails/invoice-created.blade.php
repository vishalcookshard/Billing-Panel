@component('mail::message')
# New Invoice Created

Hello {{ $user->name }},

Your invoice #{{ $invoice->id }} has been created. Please review and pay by the due date.

Thanks,<br>
{{ config('app.name') }}
@endcomponent