<?php

use CharlieLangridge\FilamentMailPreviewer\FilamentMailPreviewerServiceProvider;
use Charlielangridge\LaravelMailPreviewer\Facades\LaravelMailPreviewer;

it('boots the package service provider and loads the default config', function () {
    expect(app()->providerIsLoaded(FilamentMailPreviewerServiceProvider::class))->toBeTrue()
        ->and(config('mail-previewer.laravel_mail_previewer_facade'))
        ->toBe(LaravelMailPreviewer::class);
});

it('loads the package views', function () {
    expect(view()->exists('filament-mail-previewer::pages.mail-previewer'))->toBeTrue()
        ->and(view()->exists('filament-mail-previewer::pages.preview-mail'))->toBeTrue();
});
