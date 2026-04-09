<?php

use CharlieLangridge\FilamentMailPreviewer\FilamentMailPreviewerPlugin;
use CharlieLangridge\FilamentMailPreviewer\Pages\MailPreviewerPage;
use CharlieLangridge\FilamentMailPreviewer\Pages\PreviewMailPage;
use Filament\Facades\Filament;
use Filament\Panel;

it('registers the plugin and both pages on a panel', function () {
    $panel = Panel::make()
        ->default()
        ->id('admin')
        ->path('admin');

    $plugin = FilamentMailPreviewerPlugin::make();

    $panel->plugin($plugin);
    Filament::registerPanel($panel);
    Filament::setCurrentPanel($panel);

    expect($plugin->getId())->toBe('filament-mail-previewer')
        ->and($panel->hasPlugin('filament-mail-previewer'))->toBeTrue()
        ->and($panel->getPages())->toContain(MailPreviewerPage::class, PreviewMailPage::class)
        ->and(FilamentMailPreviewerPlugin::get())->toBe($plugin);
});

it('keeps the preview page out of navigation', function () {
    expect(PreviewMailPage::shouldRegisterNavigation())->toBeFalse();
});
