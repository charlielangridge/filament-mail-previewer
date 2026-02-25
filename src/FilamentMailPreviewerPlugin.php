<?php

namespace CharlieLangridge\FilamentMailPreviewer;

use CharlieLangridge\FilamentMailPreviewer\Pages\MailPreviewerPage;
use CharlieLangridge\FilamentMailPreviewer\Pages\PreviewMailPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMailPreviewerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-mail-previewer';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            MailPreviewerPage::class,
            PreviewMailPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
