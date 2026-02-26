<?php

namespace CharlieLangridge\FilamentMailPreviewer\Support;

use CharlieLangridge\FilamentMailPreviewer\FilamentMailPreviewerPlugin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Throwable;

class MailPreviewerAuthorization
{
    public static function canAccess(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($callback = static::resolveAuthorizationCallback()) {
            return (bool) app()->call($callback, ['user' => $user]);
        }

        $mode = (string) config('filament-mail-previewer.authorization.mode', 'none');

        return match ($mode) {
            'none' => true,
            'gate' => static::checkGate($user),
            'policy' => static::checkPolicy($user),
            default => true,
        };
    }

    protected static function checkGate(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        $ability = (string) config('filament-mail-previewer.authorization.gate_ability', 'viewFilamentMailPreviewer');

        return Gate::forUser($user)->allows($ability);
    }

    protected static function checkPolicy(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        $model = config('filament-mail-previewer.authorization.policy.model');

        if (! is_string($model) || ($model === '')) {
            return false;
        }

        $ability = (string) config('filament-mail-previewer.authorization.policy.ability', 'viewAny');

        return Gate::forUser($user)->allows($ability, $model);
    }

    /**
     * @return callable|null
     */
    protected static function resolveAuthorizationCallback(): mixed
    {
        $pluginCallback = static::resolvePluginAuthorizationCallback();

        if (is_callable($pluginCallback)) {
            return $pluginCallback;
        }

        $configCallback = config('filament-mail-previewer.authorization.callback');

        return is_callable($configCallback) ? $configCallback : null;
    }

    /**
     * @return callable|null
     */
    protected static function resolvePluginAuthorizationCallback(): mixed
    {
        try {
            if (! filament()->hasPlugin(FilamentMailPreviewerPlugin::ID)) {
                return null;
            }

            return FilamentMailPreviewerPlugin::get()->getAuthorizeUsing();
        } catch (Throwable) {
            return null;
        }
    }
}
