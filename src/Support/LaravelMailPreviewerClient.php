<?php

namespace CharlieLangridge\FilamentMailPreviewer\Support;

use Charlielangridge\LaravelMailPreviewer\Facades\LaravelMailPreviewer;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Throwable;

class LaravelMailPreviewerClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPreviewables(): array
    {
        $discovered = Cache::flexible(
            'filament-mail-previewer.discover',
            [60, 300],
            fn (): mixed => $this->callFirstAvailableMethod(['discover']),
        );

        if (is_array($discovered)) {
            $mailables = $this->normalizePreviewables(
                (array) ($discovered['mailables'] ?? []),
                'mailable',
            );

            $notifications = $this->normalizePreviewables(
                (array) ($discovered['notifications'] ?? []),
                'notification',
            );

            if (($mailables !== []) || ($notifications !== [])) {
                return [
                    ...$mailables,
                    ...$notifications,
                ];
            }
        }

        $mailables = $this->normalizePreviewables(
            $this->callFirstAvailableMethod(['mailables', 'getMailables', 'listMailables']) ?? [],
            'mailable',
        );

        $notifications = $this->normalizePreviewables(
            $this->callFirstAvailableMethod(['notifications', 'getNotifications', 'listNotifications']) ?? [],
            'notification',
        );

        return [
            ...$mailables,
            ...$notifications,
        ];
    }

    /**
     * @param  array<string, mixed>  $previewable
     * @param  array<string, mixed>  $inputs
     * @return array{name: string, subject: string, html: string, debug: array<string, mixed>}
     */
    public function renderHtml(array $previewable, array $inputs): array
    {
        $render = $this->callRenderHtml($previewable, $inputs);
        $response = $render['response'];

        $name = (string) ($previewable['name'] ?? class_basename((string) $previewable['class']));
        $subject = (string) ($previewable['subject'] ?? '');

        if (is_string($response)) {
            return [
                'name' => $name,
                'subject' => $subject,
                'html' => $response,
                'debug' => $render['debug'],
            ];
        }

        if (is_array($response)) {
            return [
                'name' => (string) ($response['name'] ?? $name),
                'subject' => (string) ($response['subject'] ?? $subject),
                'html' => (string) ($response['html'] ?? ''),
                'debug' => $render['debug'],
            ];
        }

        if (is_object($response)) {
            return [
                'name' => (string) (data_get($response, 'name') ?? $name),
                'subject' => (string) (data_get($response, 'subject') ?? $subject),
                'html' => (string) (data_get($response, 'html') ?? data_get($response, 'content') ?? ''),
                'debug' => $render['debug'],
            ];
        }

        return [
            'name' => $name,
            'subject' => $subject,
            'html' => '',
            'debug' => $render['debug'],
        ];
    }

    /**
     * @param  array<string, mixed>  $previewable
     * @param  array<string, mixed>  $inputs
     */
    public function resolveSubject(array $previewable, array $inputs): ?string
    {
        $className = (string) ($previewable['class'] ?? '');

        if (($className === '') || (! class_exists($className))) {
            return null;
        }

        try {
            $instance = $this->instantiateClass($className, $inputs);
        } catch (Throwable) {
            return null;
        }

        if ($instance instanceof Mailable) {
            return $this->resolveMailableSubject($instance);
        }

        if ($instance instanceof Notification) {
            return $this->resolveNotificationSubject($instance, $inputs);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $previewable
     * @return array<int, array<string, mixed>>
     */
    public function getInputRequirements(array $previewable): array
    {
        if (isset($previewable['inputRequirements']) && is_array($previewable['inputRequirements'])) {
            return $this->appendNotificationRequirements(
                $this->normalizeRequirements($previewable['inputRequirements']),
                $previewable,
            );
        }

        $class = (string) ($previewable['class'] ?? '');
        $type = (string) ($previewable['type'] ?? 'mailable');

        if ($class === '') {
            return [];
        }

        $requirements = [];

        foreach ([
            [$class, $type],
            [$class],
            [$type, $class],
            [$previewable],
        ] as $arguments) {
            try {
                $requirements = $this->callMethod('inputRequirements', $arguments);

                break;
            } catch (Throwable) {
                //
            }
        }

        if (! is_array($requirements)) {
            return [];
        }

        return $this->appendNotificationRequirements(
            $this->normalizeRequirements($requirements),
            $previewable,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPreviewable(string $key): ?array
    {
        return Collection::make($this->getPreviewables())
            ->first(fn (array $previewable): bool => $previewable['key'] === $key);
    }

    public function isAvailable(): bool
    {
        return class_exists($this->resolveFacadeClass());
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    public function getModelOptions(string $modelClass, ?string $search = null, int $limit = 50): array
    {
        /** @var Model $instance */
        $instance = new $modelClass;
        $titleColumn = $this->getModelTitleColumn($modelClass);
        $query = $modelClass::query();

        if (filled($search) && filled($titleColumn)) {
            $query->where($titleColumn, 'like', "%{$search}%");
        }

        return $query
            ->limit($limit)
            ->pluck($titleColumn ?: $instance->getKeyName(), $instance->getKeyName())
            ->mapWithKeys(fn ($label, $key): array => [(string) $key => (string) $label])
            ->all();
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function getModelOptionLabel(string $modelClass, string | int | null $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        /** @var Model $instance */
        $instance = new $modelClass;
        $titleColumn = $this->getModelTitleColumn($modelClass) ?: $instance->getKeyName();

        $record = $modelClass::query()->find($value);

        if (! $record) {
            return null;
        }

        return (string) ($record->getAttribute($titleColumn) ?? $record->getKey());
    }

    /**
     * @param  array<int, mixed>  $previewables
     * @return array<int, array<string, mixed>>
     */
    protected function normalizePreviewables(array $previewables, string $type): array
    {
        return Collection::make($previewables)
            ->map(fn (mixed $previewable): array => $this->normalizePreviewable($previewable, $type))
            ->filter(fn (array $previewable): bool => filled($previewable['class'] ?? null))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizePreviewable(mixed $previewable, string $type): array
    {
        if (is_string($previewable)) {
            $previewable = [
                'class' => $previewable,
            ];
        }

        if (is_object($previewable)) {
            $previewable = (array) $previewable;
        }

        if (! is_array($previewable)) {
            $previewable = [];
        }

        $class = (string) (
            Arr::get($previewable, 'class')
            ?? Arr::get($previewable, 'fqcn')
            ?? Arr::get($previewable, 'name')
            ?? ''
        );

        $name = (string) (
            Arr::get($previewable, 'name')
            ?? Arr::get($previewable, 'label')
            ?? class_basename($class)
        );

        $subject = (string) (
            Arr::get($previewable, 'subject')
            ?? Arr::get($previewable, 'subjectLine')
            ?? ''
        );

        $normalized = [
            ...$previewable,
            'type' => (string) (Arr::get($previewable, 'type') ?? $type),
            'class' => $class,
            'name' => $name,
            'subject' => $subject,
        ];

        $normalized['key'] = sha1($normalized['type'] . '|' . $normalized['class']);
        $normalized['inputRequirements'] = $this->normalizeRequirements((array) (
            Arr::get($previewable, 'inputRequirements')
            ?? Arr::get($previewable, 'input_requirements')
            ?? []
        ));

        if ($normalized['inputRequirements'] === []) {
            $normalized['inputRequirements'] = $this->getInputRequirements($normalized);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $previewable
     * @param  array<string, mixed>  $inputs
     * @return array{response: mixed, debug: array<string, mixed>}
     */
    protected function callRenderHtml(array $previewable, array $inputs): array
    {
        $class = (string) ($previewable['class'] ?? '');
        $type = (string) ($previewable['type'] ?? 'mailable');
        $notifiable = $inputs['notifiable'] ?? null;

        if (array_key_exists('notifiable', $inputs)) {
            unset($inputs['notifiable']);
        }

        $attempts = [
            [$class, $inputs, $notifiable],
            [$class, $inputs],
            [$previewable, $inputs],
        ];

        $errors = [];

        foreach ($attempts as $index => $arguments) {
            try {
                $response = $this->callMethod('renderHtml', $arguments);

                return [
                    'response' => $response,
                    'debug' => [
                        'type' => $type,
                        'class' => $class,
                        'attempt' => $index + 1,
                        'had_notifiable' => $notifiable !== null,
                        'empty_response' => blank($response),
                        'errors' => $errors,
                    ],
                ];
            } catch (Throwable $exception) {
                $errors[] = 'attempt ' . ($index + 1) . ': ' . $exception->getMessage();
            }
        }

        return [
            'response' => null,
            'debug' => [
                'type' => $type,
                'class' => $class,
                'had_notifiable' => $notifiable !== null,
                'errors' => $errors,
            ],
        ];
    }

    protected function callFirstAvailableMethod(array $methodNames): mixed
    {
        foreach ($methodNames as $methodName) {
            try {
                return $this->callMethod($methodName, []);
            } catch (Throwable) {
                //
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    protected function callMethod(string $method, array $arguments): mixed
    {
        $facadeClass = $this->resolveFacadeClass();

        if (! class_exists($facadeClass)) {
            throw new RuntimeException("The facade [{$facadeClass}] is not installed.");
        }

        return $facadeClass::$method(...$arguments);
    }

    protected function resolveFacadeClass(): string
    {
        return (string) (
            config('mail-previewer.laravel_mail_previewer_facade')
            ?? config('filament-mail-previewer.laravel_mail_previewer_facade')
            ?? LaravelMailPreviewer::class
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function getModelTitleColumn(string $modelClass): ?string
    {
        $panel = Filament::getCurrentPanel();
        $resourceClass = $panel?->getModelResource($modelClass);

        if ($resourceClass && method_exists($resourceClass, 'getRecordTitleAttribute')) {
            return $resourceClass::getRecordTitleAttribute() ?: null;
        }

        return null;
    }

    /**
     * @param  class-string  $className
     * @param  array<string, mixed>  $parameters
     */
    protected function instantiateClass(string $className, array $parameters): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), $parameters)) {
                $arguments[] = $this->coerceParameterValue($parameter, $parameters[$parameter->getName()]);

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            throw new RuntimeException('Missing required parameter: ' . $parameter->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }

    protected function coerceParameterValue(ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $value;
        }

        $className = $type->getName();

        if (! is_a($className, Model::class, true)) {
            return $value;
        }

        if ($value instanceof $className) {
            return $value;
        }

        if (is_scalar($value)) {
            return $className::query()->findOrFail($value);
        }

        return $value;
    }

    protected function resolveMailableSubject(Mailable $mailable): ?string
    {
        try {
            if (method_exists($mailable, 'envelope')) {
                $envelope = $mailable->envelope();
                $subject = is_object($envelope) ? data_get($envelope, 'subject') : null;

                if (is_string($subject) && filled($subject)) {
                    return $subject;
                }
            }
        } catch (Throwable) {
            //
        }

        try {
            if (method_exists($mailable, 'build')) {
                $mailable->build();
            }
        } catch (Throwable) {
            //
        }

        $subject = data_get($mailable, 'subject');

        return is_string($subject) && filled($subject) ? $subject : null;
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    protected function resolveNotificationSubject(Notification $notification, array $inputs): ?string
    {
        if (! method_exists($notification, 'toMail')) {
            return null;
        }

        $notifiable = $inputs['notifiable'] ?? (new AnonymousNotifiable)->route('mail', 'preview@example.test');

        try {
            $mailRepresentation = $notification->toMail($notifiable);
        } catch (Throwable) {
            return null;
        }

        if ($mailRepresentation instanceof MailMessage) {
            $subject = $mailRepresentation->subject;

            return filled($subject) ? $subject : null;
        }

        if ($mailRepresentation instanceof Mailable) {
            return $this->resolveMailableSubject($mailRepresentation);
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $requirements
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRequirements(array $requirements): array
    {
        return Collection::make($requirements)
            ->map(fn (mixed $requirement, int | string $key): array => $this->normalizeRequirement($requirement, $key))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeRequirement(mixed $requirement, int | string $key): array
    {
        if (is_string($requirement)) {
            return [
                'name' => $requirement,
                'type' => 'string',
                'required' => true,
            ];
        }

        if (is_object($requirement)) {
            $requirement = (array) $requirement;
        }

        if (! is_array($requirement)) {
            return [
                'name' => (string) $key,
                'type' => 'string',
                'required' => true,
            ];
        }

        $name = (string) ($requirement['name'] ?? $key);

        $normalized = [
            ...$requirement,
            'name' => $name,
            'required' => (bool) ($requirement['required'] ?? true),
            'type' => (string) ($requirement['type'] ?? 'string'),
        ];

        $options = (array) ($normalized['options'] ?? []);

        if ($options !== []) {
            $normalized['options'] = Collection::make($options)
                ->mapWithKeys(function (mixed $option): array {
                    if (is_array($option) && array_key_exists('id', $option)) {
                        return [(string) $option['id'] => (string) ($option['label'] ?? $option['id'])];
                    }

                    if (is_scalar($option)) {
                        return [(string) $option => (string) $option];
                    }

                    return [];
                })
                ->all();
        }

        $type = (string) $normalized['type'];

        if (
            (! isset($normalized['model'])) &&
            class_exists($type) &&
            is_subclass_of($type, Model::class)
        ) {
            $normalized['model'] = $type;
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $requirements
     * @param  array<string, mixed>  $previewable
     * @return array<int, array<string, mixed>>
     */
    protected function appendNotificationRequirements(array $requirements, array $previewable): array
    {
        if (($previewable['type'] ?? null) !== 'notification') {
            return $requirements;
        }

        $hasNotifiableRequirement = Collection::make($requirements)
            ->contains(fn (array $requirement): bool => ($requirement['name'] ?? null) === 'notifiable');

        if ($hasNotifiableRequirement) {
            return $requirements;
        }

        $notifiable = auth()->user();

        if (! $notifiable instanceof Model) {
            return $requirements;
        }

        $notifiableModel = $notifiable::class;

        $requirements[] = [
            'name' => 'notifiable',
            'label' => 'Notifiable',
            'type' => 'model',
            'model' => $notifiableModel,
            'required' => false,
        ];

        return $requirements;
    }
}
