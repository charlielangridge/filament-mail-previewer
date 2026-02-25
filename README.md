# Filament Mail Previewer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/charlielangridge/filament-mail-previewer.svg?style=flat-square)](https://packagist.org/packages/charlielangridge/filament-mail-previewer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/charlielangridge/filament-mail-previewer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/charlielangridge/filament-mail-previewer/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/charlielangridge/filament-mail-previewer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/charlielangridge/filament-mail-previewer/actions?query=workflow%3A%22Fix+PHP+code+styling%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/charlielangridge/filament-mail-previewer.svg?style=flat-square)](https://packagist.org/packages/charlielangridge/filament-mail-previewer)

Filament Mail Previewer adds a Filament page that discovers your app mailables and notifications, asks for any required constructor inputs, and renders the email HTML side-by-side in desktop and mobile frames.

It uses [`charlielangridge/laravel-mail-previewer`](https://packagist.org/packages/charlielangridge/laravel-mail-previewer) under the hood to discover classes and render previews.

## Requirements

- PHP `^8.3`
- Laravel app with Filament `^5.0`
- A Filament panel where you can register plugins

## Installation

Install the package:

```bash
composer require charlielangridge/filament-mail-previewer
```

The package auto-discovers its service provider.

## Register The Plugin In Your Filament Panel

Add the plugin to your panel provider (example: `app/Providers/Filament/AdminPanelProvider.php`):

```php
use CharlieLangridge\FilamentMailPreviewer\FilamentMailPreviewerPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentMailPreviewerPlugin::make(),
        ]);
}
```

This registers:

- a navigation page: `Mail Previewer`
- a preview page used after selecting a mailable/notification

## Filament Theme Setup (Important)

If you are using a custom Filament theme, include this package's Blade files in your Tailwind content sources so page styles/classes are picked up:

```css
@source '../../../../vendor/charlielangridge/filament-mail-previewer/resources/**/*.blade.php';
```

Then rebuild your assets.

If you have not created a custom Filament theme yet, follow the Filament docs first:

- https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme

## Optional Configuration

Publish config if you want to override the facade class used to talk to the underlying Laravel mail previewer package:

```bash
php artisan vendor:publish --tag="filament-mail-previewer-config"
```

Published config:

```php
return [
    'laravel_mail_previewer_facade' => \Charlielangridge\LaravelMailPreviewer\Facades\LaravelMailPreviewer::class,
];
```

In most apps you do not need to change this.

## Usage

1. Open your Filament panel.
2. Go to `Mail Previewer` in navigation.
3. Select a mailable or notification row and click `Preview`.
4. Fill required inputs in the modal.
5. Submit to open the rendered email preview page.

The preview page shows:

- desktop frame
- mobile frame
- heading + resolved subject

## How Inputs Are Handled

The plugin automatically builds a form from discovered constructor/input requirements:

- `model` inputs become searchable selects
- `array` inputs are entered as JSON
- `integer` inputs are numeric fields
- fields with `date` in the name become date pickers
- other values are captured in textareas

For notifications, a `notifiable` model input is added automatically when possible (usually defaults to the authenticated user in the modal).

## Troubleshooting

If the table is empty or you see the install notice:

- ensure `charlielangridge/laravel-mail-previewer` is installed (it is a dependency of this plugin)
- ensure your mailables/notifications are discoverable in your app
- clear caches and reload:

```bash
php artisan optimize:clear
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [.github/CONTRIBUTING.md](.github/CONTRIBUTING.md).

## Security

See [.github/SECURITY.md](.github/SECURITY.md).

## Credits

- [Charlie Langridge](https://github.com/charlielangridge)
- [All Contributors](../../contributors)

## License

MIT. See [LICENSE.md](LICENSE.md).
