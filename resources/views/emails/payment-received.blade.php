@component('mail::message')
# Payment Received

Hello {{ $user->name }},

Your payment for invoice #{{ $invoice->id }} has been received. Thank you!

Thanks,<br>
{{ config('app.name') }}
@endcomponent