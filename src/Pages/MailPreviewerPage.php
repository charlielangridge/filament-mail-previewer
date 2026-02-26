<?php

namespace CharlieLangridge\FilamentMailPreviewer\Pages;

use CharlieLangridge\FilamentMailPreviewer\Support\LaravelMailPreviewerClient;
use CharlieLangridge\FilamentMailPreviewer\Support\MailPreviewerAuthorization;
use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class MailPreviewerPage extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationLabel = 'Mail Previewer';

    protected static ?string $slug = 'mail-previewer';

    protected static ?int $navigationSort = 80;

    protected string $view = 'filament-mail-previewer::pages.mail-previewer';

    public static function canAccess(): bool
    {
        return MailPreviewerAuthorization::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->getPreviewables())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Subject')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('class')
                    ->label('FQN')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->badge()
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->button()
                    ->icon('heroicon-o-eye')
                    ->modalSubmitActionLabel('Preview Email')
                    ->modalHeading(fn (array $record): string => $this->getModalHeading($record))
                    ->schema(fn (array $record): array => $this->getInputSchemaForPreviewable($record))
                    ->fillForm(fn (array $record): array => $this->getInputDefaults($record))
                    ->action(function (array $record, array $data): void {
                        $previewable = $this->getClient()->findPreviewable((string) ($record['key'] ?? '')) ?? $record;

                        if (! isset($previewable['class'])) {
                            Notification::make()
                                ->danger()
                                ->title('Previewable not found.')
                                ->send();

                            return;
                        }

                        $normalizedInputs = $this->normalizeInputData($previewable, (array) Arr::get($data, 'inputs', []));
                        $preview = $this->getClient()->renderHtml($previewable, $normalizedInputs);
                        $subject = $this->getClient()->resolveSubject($previewable, $normalizedInputs) ?? $preview['subject'];

                        session()->put('filament-mail-previewer.preview', [
                            'type' => $previewable['type'],
                            'class' => $previewable['class'],
                            'name' => $preview['name'],
                            'subject' => $subject,
                            'html' => $preview['html'],
                            'debug' => $preview['debug'],
                        ]);

                        redirect(PreviewMailPage::getUrl());
                    }),
            ])
            ->emptyStateHeading('No mailables or notifications were found.');
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Mail Previewer';
    }

    public function getSubheading(): ?string
    {
        if ($this->getClient()->isAvailable()) {
            return 'Preview mailables and notifications from your app.';
        }

        return 'Install and configure charlielangridge/laravel-mail-previewer to use this tool.';
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseTable()
            ->recordTitle(fn (array | Model $record): string => (string) data_get($record, 'name', 'Previewable'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPreviewables(): array
    {
        return $this->getClient()->getPreviewables();
    }

    /**
     * @param  array<string, mixed>  $previewable
     */
    protected function getModalHeading(array $previewable): string
    {
        return 'Preview ' . ($previewable['name'] ?? 'Email');
    }

    /**
     * @return array<int, \Filament\Forms\Components\Field>
     */
    protected function getInputSchemaForPreviewable(array $previewable): array
    {
        if (! isset($previewable['class'])) {
            return [];
        }

        return collect($this->getClient()->getInputRequirements($previewable))
            ->map(fn (array $requirement): \Filament\Forms\Components\Field => $this->buildInputComponent($requirement))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $previewable
     * @return array<string, mixed>
     */
    protected function getInputDefaults(array $previewable): array
    {
        $defaults = [];

        foreach ($this->getClient()->getInputRequirements($previewable) as $requirement) {
            if (($requirement['name'] ?? null) !== 'notifiable') {
                continue;
            }

            $user = auth()->user();

            if ($user) {
                $defaults['notifiable'] = (string) $user->getKey();
            }
        }

        return [
            'inputs' => $defaults,
        ];
    }

    /**
     * @param  array<string, mixed>  $requirement
     */
    protected function buildInputComponent(array $requirement): \Filament\Forms\Components\Field
    {
        $name = (string) ($requirement['name'] ?? $requirement['key'] ?? 'input');
        $path = "inputs.{$name}";
        $label = (string) ($requirement['label'] ?? str($name)->headline()->toString());
        $required = (bool) ($requirement['required'] ?? true);
        $type = strtolower((string) ($requirement['type'] ?? 'string'));

        if ($this->isModelRequirement($requirement)) {
            return $this->buildModelSelect($path, $label, $required, $requirement);
        }

        if ($type === 'array') {
            return CodeEditor::make($path)
                ->label($label)
                ->language(Language::Json)
                ->helperText('Provide JSON array input.')
                ->required($required);
        }

        if (str($name)->lower()->contains('date')) {
            return DatePicker::make($path)
                ->label($label)
                ->native(false)
                ->required($required);
        }

        if ($type === 'integer') {
            return TextInput::make($path)
                ->label($label)
                ->numeric()
                ->inputMode('numeric')
                ->required($required);
        }

        return Textarea::make($path)
            ->label($label)
            ->autosize()
            ->required($required);
    }

    /**
     * @param  array<string, mixed>  $requirement
     */
    protected function buildModelSelect(string $statePath, string $label, bool $required, array $requirement): Select
    {
        $modelClass = (string) ($requirement['model'] ?? '');
        $fallbackOptions = (array) ($requirement['options'] ?? []);
        $isModelClass = filled($modelClass) && is_subclass_of($modelClass, Model::class);

        $select = Select::make($statePath)
            ->label($label)
            ->required($required)
            ->searchable();

        if (! $isModelClass) {
            return $select->options($fallbackOptions);
        }

        return $select
            ->getSearchResultsUsing(fn (?string $search): array => $this->getClient()->getModelOptions($modelClass, $search))
            ->getOptionLabelUsing(fn (string | int | null $value): ?string => $this->getClient()->getModelOptionLabel($modelClass, $value))
            ->options(fn (): array => $this->getClient()->getModelOptions($modelClass, limit: 25));
    }

    /**
     * @param  array<string, mixed>  $requirement
     */
    protected function isModelRequirement(array $requirement): bool
    {
        $type = strtolower((string) ($requirement['type'] ?? ''));
        $model = (string) ($requirement['model'] ?? '');

        return ($type === 'model') || ($model !== '');
    }

    /**
     * @param  array<string, mixed>  $previewable
     * @param  array<string, mixed>  $rawInputs
     * @return array<string, mixed>
     */
    protected function normalizeInputData(array $previewable, array $rawInputs): array
    {
        $requirements = $this->getClient()->getInputRequirements($previewable);
        $inputs = [];
        $errors = [];

        foreach ($requirements as $requirement) {
            $name = (string) ($requirement['name'] ?? $requirement['key'] ?? '');

            if ($name === '') {
                continue;
            }

            $value = $rawInputs[$name] ?? null;
            $type = strtolower((string) ($requirement['type'] ?? 'string'));

            if (($type === 'array') && is_string($value) && filled($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() !== JSON_ERROR_NONE || (! is_array($decoded))) {
                    $errors["mountedActions.0.data.inputs.{$name}"] = "The {$name} field must contain valid JSON array input.";

                    continue;
                }

                $value = $decoded;
            }

            if ($this->isModelRequirement($requirement)) {
                $modelClass = (string) ($requirement['model'] ?? '');

                if (filled($modelClass) && is_subclass_of($modelClass, Model::class) && filled($value)) {
                    $value = $modelClass::query()->find($value);
                }
            }

            $inputs[$name] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $inputs;
    }

    protected function getClient(): LaravelMailPreviewerClient
    {
        return app(LaravelMailPreviewerClient::class);
    }
}
