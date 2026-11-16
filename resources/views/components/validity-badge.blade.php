@props(['document'])
{{-- Status keberlakuan dokumen (Berlaku / Digantikan / Dicabut), plus
     "Kadaluarsa" when the expiry date has passed. --}}
@php($validity = $document->validity ?? \App\Enums\DocumentValidity::Berlaku)
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.$validity->badgeClasses()]) }}>
    @if ($validity === \App\Enums\DocumentValidity::Berlaku)
        <x-heroicon-m-check-circle class="h-3.5 w-3.5" />
    @else
        <x-heroicon-m-arrow-path class="h-3.5 w-3.5" />
    @endif
    {{ $validity->getLabel() }}
</span>
@if ($document->isExpired() && $validity === \App\Enums\DocumentValidity::Berlaku)
    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
        <x-heroicon-m-clock class="h-3.5 w-3.5" /> Kadaluarsa
    </span>
@endif
