<x-filament-panels::page>
    <div class="grid gap-6">
        {{-- Ringkasan status --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([
                ['icon' => 'heroicon-o-clock', 'label' => 'Pending', 'value' => $statusSummary['pending'], 'accent' => 'from-amber-400 to-amber-300'],
                ['icon' => 'heroicon-o-check-badge', 'label' => 'Approved', 'value' => $statusSummary['approved'], 'accent' => 'from-blue-500 to-blue-400'],
                ['icon' => 'heroicon-o-globe-alt', 'label' => 'Published', 'value' => $statusSummary['published'], 'accent' => 'from-emerald-500 to-emerald-400'],
                ['icon' => 'heroicon-o-archive-box', 'label' => 'Archived', 'value' => $statusSummary['archived'], 'accent' => 'from-gray-400 to-gray-300'],
            ] as $card)
                <div class="relative overflow-hidden rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $card['accent'] }}"></div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-white/5">
                            <x-dynamic-component :component="$card['icon']" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                            <p class="mt-0.5 text-2xl font-semibold text-gray-800 dark:text-white">{{ $card['value'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section heading="Jumlah Dokumen per Tahun" icon="heroicon-o-calendar-days">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                <th class="px-3 py-2">Tahun</th>
                                <th class="px-3 py-2 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($perYear as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">{{ $row->year }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-3 py-6 text-center text-sm text-gray-400">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section heading="Jumlah Dokumen per Unit" icon="heroicon-o-building-office-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                <th class="px-3 py-2">Unit</th>
                                <th class="px-3 py-2 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($perUnit as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">{{ $row->unit_name }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-3 py-6 text-center text-sm text-gray-400">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section heading="Jumlah Dokumen per Kategori" icon="heroicon-o-folder-open">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                <th class="px-3 py-2">Kategori</th>
                                <th class="px-3 py-2 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($perCategory as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">{{ $row->category_name }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-3 py-6 text-center text-sm text-gray-400">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section heading="Dokumen Paling Banyak Diunduh" icon="heroicon-o-arrow-down-tray">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                <th class="px-3 py-2">Judul</th>
                                <th class="px-3 py-2 text-right">Unduhan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($mostDownloaded as $doc)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="max-w-0 truncate px-3 py-2 font-medium text-gray-700 dark:text-gray-200">{{ $doc->title }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ $doc->download_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-3 py-6 text-center text-sm text-gray-400">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section heading="Aktivitas User" icon="heroicon-o-user-group" class="lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                <th class="px-3 py-2">User</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2 text-right">Jumlah Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($userActivity as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-200">{{ $row->causer?->name ?? 'Unknown' }}</td>
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $row->causer?->email ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-6 text-center text-sm text-gray-400">Belum ada aktivitas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
