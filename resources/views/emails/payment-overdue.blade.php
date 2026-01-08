@component('mail::message')
# Payment Overdue

Hello {{ $user->name }},

Your invoice #{{ $invoice->id }} is overdue. Please pay as soon as possible to avoid service interruption.

Thanks,<br>
{{ config('app.name') }}
@endcomponent