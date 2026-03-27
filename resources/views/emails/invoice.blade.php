<x-mail::message>
{{ $greeting }}

{{ $body }}

@if ($paymentInstructions)
**Payment Instructions**

{{ $paymentInstructions }}
@endif

{{ $footer }}

Thanks,<br>
{{ $businessName }}
</x-mail::message>
