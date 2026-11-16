<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Pilih menu</x-slot>
        <x-slot name="description">Pilih subkategori, lalu geser dokumen (tarik ikon ⋮⋮) ke urutan yang diinginkan. Urutan ini yang tampil di web.</x-slot>

        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="menu">
                <option value="">— Pilih subkategori —</option>
                @foreach ($this->getMenuOptions() as $category => $group)
                    <optgroup label="{{ $category }}">
                        @foreach ($group as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </x-filament::section>

    @if ($menu)
        @php($documents = $this->getDocuments())

        <x-filament::section>
            <x-slot name="heading">{{ $this->getSelectedMenuLabel() }}</x-slot>
            <x-slot name="description">{{ $documents->count() }} dokumen &middot; urutan langsung tersimpan setiap selesai menggeser.</x-slot>
            <x-slot name="afterHeader">
                @if ($documents->whereNotNull('sort_order')->isNotEmpty())
                    <x-filament::button color="gray" size="sm" icon="heroicon-m-arrow-path"
                        wire:click="resetOrder"
                        wire:confirm="Kembalikan urutan menu ini ke otomatis (tahun & Tanggal Berlaku terbaru di atas)?">
                        Kembalikan ke otomatis
                    </x-filament::button>
                @endif
            </x-slot>

            @if ($documents->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada dokumen di menu ini.</p>
            @else
                <ul
                    x-sortable
                    x-on:end.stop="$wire.saveOrder($event.target.sortable.toArray())"
                    class="divide-y divide-gray-100 overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:divide-white/5 dark:ring-white/10"
                    wire:key="list-{{ $menu }}-{{ $documents->pluck('id')->implode('-') }}"
                >
                    @foreach ($documents as $document)
                        <li x-sortable-item="{{ $document->id }}" wire:key="doc-{{ $document->id }}"
                            class="flex items-center gap-3 bg-white px-3 py-2.5 dark:bg-gray-900">
                            <button type="button" x-sortable-handle
                                class="cursor-grab text-gray-400 hover:text-gray-600 active:cursor-grabbing dark:text-gray-500"
                                title="Geser untuk memindahkan">
                                <x-filament::icon icon="heroicon-m-bars-3" class="h-5 w-5" />
                            </button>
                            <span class="w-7 shrink-0 text-center text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $document->title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $document->year }}
                                    @if ($document->effective_date) &middot; berlaku {{ $document->effective_date->format('d/m/Y') }} @endif
                                    @if ($document->sort_order === null) &middot; <em>otomatis</em> @endif
                                </p>
                            </div>
                            <x-filament::badge :color="$document->validity->getColor()" size="sm">{{ $document->validity->getLabel() }}</x-filament::badge>
                            @if ($document->status !== \App\Enums\DocumentStatus::Published)
                                <x-filament::badge color="gray" size="sm">{{ $document->status->getLabel() }} (tidak tampil di web)</x-filament::badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
