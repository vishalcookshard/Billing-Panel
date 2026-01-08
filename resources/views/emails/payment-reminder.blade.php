@component('mail::message')
# Payment Reminder

Hello {{ $user->name }},

Your invoice #{{ $invoice->id }} is due in {{ $daysLeft }} day(s).

Thanks,<br>
{{ config('app.name') }}
@endcomponent