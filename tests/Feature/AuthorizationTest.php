<?php

use CharlieLangridge\FilamentMailPreviewer\Pages\MailPreviewerPage;
use CharlieLangridge\FilamentMailPreviewer\Pages\PreviewMailPage;
use CharlieLangridge\FilamentMailPreviewer\Tests\Fixtures\DummyUser;
use CharlieLangridge\FilamentMailPreviewer\Tests\Fixtures\MailPreviewerAccess;
use CharlieLangridge\FilamentMailPreviewer\Tests\Fixtures\MailPreviewerAccessPolicy;
use Illuminate\Support\Facades\Gate;

it('allows access by default', function () {
    config()->set('filament-mail-previewer.authorization.mode', 'none');

    expect(MailPreviewerPage::canAccess())->toBeTrue()
        ->and(PreviewMailPage::canAccess())->toBeTrue();
});

it('supports gate-based authorization', function () {
    config()->set('filament-mail-previewer.authorization.mode', 'gate');
    config()->set('filament-mail-previewer.authorization.gate_ability', 'viewFilamentMailPreviewer');

    Gate::define('viewFilamentMailPreviewer', fn (DummyUser $user): bool => (bool) $user->getAttribute('can_preview_mail'));

    auth()->setUser(new DummyUser(['can_preview_mail' => false]));
    expect(MailPreviewerPage::canAccess())->toBeFalse()
        ->and(PreviewMailPage::canAccess())->toBeFalse();

    auth()->setUser(new DummyUser(['can_preview_mail' => true]));
    expect(MailPreviewerPage::canAccess())->toBeTrue()
        ->and(PreviewMailPage::canAccess())->toBeTrue();
});

it('supports policy-based authorization', function () {
    config()->set('filament-mail-previewer.authorization.mode', 'policy');
    config()->set('filament-mail-previewer.authorization.policy.model', MailPreviewerAccess::class);
    config()->set('filament-mail-previewer.authorization.policy.ability', 'viewAny');

    Gate::policy(MailPreviewerAccess::class, MailPreviewerAccessPolicy::class);

    auth()->setUser(new DummyUser(['can_preview_mail' => false]));
    expect(MailPreviewerPage::canAccess())->toBeFalse();

    auth()->setUser(new DummyUser(['can_preview_mail' => true]));
    expect(MailPreviewerPage::canAccess())->toBeTrue();
});

it('lets callback override mode checks', function () {
    config()->set('filament-mail-previewer.authorization.mode', 'gate');
    config()->set('filament-mail-previewer.authorization.callback', fn (?DummyUser $user): bool => (bool) $user?->getAttribute('can_preview_mail'));

    auth()->setUser(new DummyUser(['can_preview_mail' => false]));
    expect(MailPreviewerPage::canAccess())->toBeFalse();

    auth()->setUser(new DummyUser(['can_preview_mail' => true]));
    expect(MailPreviewerPage::canAccess())->toBeTrue();
});
