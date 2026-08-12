{{--
    Universal "Kembali" button, injected panel-wide via a render hook
    (AdminPanelProvider::PAGE_HEADER_ACTIONS_BEFORE) instead of being added to
    each resource's page class individually — one place, applies everywhere.
    Plain browser history.back() so it works the same regardless of which
    resource/page it's on, with no per-resource "index route" wiring needed.
--}}
<x-filament::button
    tag="button"
    type="button"
    color="gray"
    icon="heroicon-o-arrow-left"
    x-on:click="window.history.length > 1 ? window.history.back() : (window.location.href = '{{ url('/admin') }}')"
>
    Kembali
</x-filament::button>
