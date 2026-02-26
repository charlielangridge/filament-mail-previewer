<?php

namespace CharlieLangridge\FilamentMailPreviewer\Pages;

use CharlieLangridge\FilamentMailPreviewer\Support\MailPreviewerAuthorization;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class PreviewMailPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'mail-previewer/preview';

    protected string $view = 'filament-mail-previewer::pages.preview-mail';

    /**
     * @var array<string, mixed>
     */
    public array $preview = [];

    public static function canAccess(): bool
    {
        return MailPreviewerAuthorization::canAccess();
    }

    public function mount(): void
    {
        $this->preview = (array) session()->get('filament-mail-previewer.preview', []);
    }

    public function getHeading(): string | Htmlable | null
    {
        return (string) ($this->preview['name'] ?? 'Email Preview');
    }

    public function getSubheading(): ?string
    {
        $subject = (string) ($this->preview['subject'] ?? '');

        return $subject !== '' ? "Subject: {$subject}" : 'No subject defined';
    }

    /**
     * @return array<string|int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            MailPreviewerPage::getUrl() => MailPreviewerPage::getNavigationLabel(),
            (string) ($this->preview['name'] ?? 'Preview'),
        ];
    }
}
