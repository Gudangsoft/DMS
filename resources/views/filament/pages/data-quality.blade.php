<x-filament-panels::page>
    @php($checks = $this->getChecks())
    @php($counts = $this->getCounts())

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($checks as $key => $meta)
            @php($active = $check === $key)
            <button type="button" wire:click="setCheck('{{ $key }}')"
                @class([
                    'rounded-xl p-4 text-left ring-1 transition',
                    'bg-white ring-gray-950/5 hover:ring-gray-950/20 dark:bg-gray-900 dark:ring-white/10' => ! $active,
                    'bg-primary-50 ring-2 ring-primary-600 dark:bg-primary-950/40' => $active,
                ])>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $meta['label'] }}</span>
                    <x-filament::badge :color="$counts[$key] > 0 ? $meta['color'] : 'success'">
                        {{ number_format($counts[$key], 0, ',', '.') }}
                    </x-filament::badge>
                </div>
            </button>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">{{ $checks[$check]['label'] }}</x-slot>
        <x-slot name="description">{{ $checks[$check]['description'] }}</x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
