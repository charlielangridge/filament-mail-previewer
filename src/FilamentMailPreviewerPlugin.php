<?php

namespace CharlieLangridge\FilamentMailPreviewer;

use CharlieLangridge\FilamentMailPreviewer\Pages\MailPreviewerPage;
use CharlieLangridge\FilamentMailPreviewer\Pages\PreviewMailPage;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMailPreviewerPlugin implements Plugin
{
    public const ID = 'filament-mail-previewer';

    protected ?Closure $authorizeUsing = null;

    public function getId(): string
    {
        return static::ID;
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

    public function authorizeUsing(?Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function getAuthorizeUsing(): ?Closure
    {
        return $this->authorizeUsing;
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
