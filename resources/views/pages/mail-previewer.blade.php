<x-filament-panels::page>
    @if (! app(\CharlieLangridge\FilamentMailPreviewer\Support\LaravelMailPreviewerClient::class)->isAvailable())
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Install <code>charlielangridge/laravel-mail-previewer</code> to load mailables and notifications.
            </p>
        </x-filament::section>
    @else
        {{ $this->table }}
    @endif
</x-filament-panels::page>
