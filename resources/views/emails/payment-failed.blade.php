@component('mail::message')
# Payment Failed

Hello {{ $user->name }},

Your payment for invoice #{{ $invoice->id }} failed. Please try again or contact support.

Thanks,<br>
{{ config('app.name') }}
@endcomponent