<x-filament-panels::page>
    @if (filled($this->preview['html'] ?? null))
        <x-filament::section>
            <div
                class="flex flex-col gap-6 xl:flex-row xl:items-start"
                style="display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-start;"
            >
                <div class="w-full max-w-3xl" style="width: 100%; max-width: 48rem; flex: 1 1 48rem;">
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Desktop</p>
                    <div
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-950"
                        style="overflow: hidden; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #fff;"
                    >
                        <iframe
                            class="w-full bg-white"
                            style="height: auto; max-height: 70vh; width: 100%; border: 0; background: #fff;"
                            sandbox="allow-same-origin"
                            x-data="{}"
                            x-init="
                                const setHeight = () => {
                                    const doc = $el.contentDocument;
                                    if (! doc) return;
                                    const contentHeight = Math.max(
                                        doc.body?.scrollHeight ?? 0,
                                        doc.documentElement?.scrollHeight ?? 0
                                    );
                                    const maxHeight = Math.floor(window.innerHeight * 0.7);
                                    $el.style.height = Math.min(contentHeight, maxHeight) + 'px';
                                };

                                $el.addEventListener('load', () => {
                                    setHeight();
                                    setTimeout(setHeight, 100);
                                    setTimeout(setHeight, 500);
                                });

                                window.addEventListener('resize', setHeight);
                                $el.srcdoc = @js((string) $this->preview['html']);
                            "
                        ></iframe>
                    </div>
                </div>

                <div class="w-full max-w-sm" style="width: 100%; max-width: 24rem; flex: 0 1 24rem;">
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Mobile</p>
                    <div
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-950"
                        style="overflow: hidden; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #fff;"
                    >
                        <iframe
                            class="w-full bg-white"
                            style="height: auto; max-height: 70vh; width: 100%; border: 0; background: #fff;"
                            sandbox="allow-same-origin"
                            x-data="{}"
                            x-init="
                                const setHeight = () => {
                                    const doc = $el.contentDocument;
                                    if (! doc) return;
                                    const contentHeight = Math.max(
                                        doc.body?.scrollHeight ?? 0,
                                        doc.documentElement?.scrollHeight ?? 0
                                    );
                                    const maxHeight = Math.floor(window.innerHeight * 0.7);
                                    $el.style.height = Math.min(contentHeight, maxHeight) + 'px';
                                };

                                $el.addEventListener('load', () => {
                                    setHeight();
                                    setTimeout(setHeight, 100);
                                    setTimeout(setHeight, 500);
                                });

                                window.addEventListener('resize', setHeight);
                                $el.srcdoc = @js((string) $this->preview['html']);
                            "
                        ></iframe>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                No preview is available yet. Select a mailable or notification first.
            </p>

            @if (! empty($this->preview['debug'] ?? []))
                <div class="mt-4 overflow-auto rounded-lg border border-gray-200 bg-white p-3 text-xs dark:border-white/10 dark:bg-gray-950">
                    <pre>{{ json_encode($this->preview['debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
