<?php

namespace CharlieLangridge\FilamentMailPreviewer\Tests\Fixtures;

class MailPreviewerAccessPolicy
{
    public function viewAny(DummyUser $user): bool
    {
        return (bool) $user->getAttribute('can_preview_mail');
    }
}
