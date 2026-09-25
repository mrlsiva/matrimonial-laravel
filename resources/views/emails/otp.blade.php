<x-mail::message>
# Your verification code

Use the code below to {{ $purpose === 'login' ? 'sign in to' : 'verify your account on' }} {{ config('app.name') }}.

<x-mail::panel>
<div style="font-size:28px;letter-spacing:8px;text-align:center;font-weight:bold">{{ $code }}</div>
</x-mail::panel>

This code expires in {{ $minutes }} minutes. If you did not request it, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
