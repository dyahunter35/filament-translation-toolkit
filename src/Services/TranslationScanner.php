<?php

namespace Dyahunter35\FilamentTranslationToolkit\Services;

use Dyahunter35\FilamentTranslationToolkit\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class TranslationScanner
{
    protected string $langPath;
    protected array $locales;
    protected string $modelNamespace;

    public function __construct()
    {
        $this->langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');
        $this->locales = config('filament-translation-toolkit.locales', ['en', 'ar']);
        $this->modelNamespace = config('filament-translation-toolkit.model_namespace', 'App\\Models');
    }

    /**
     * Get all translatable models (models that use the Translatable trait).
     */
    public function getTranslatableModels(): array
    {
        $models = $this->discoverModels();
        $results = [];

        foreach ($models as $modelClass) {
            if (!$this->usesTranslatableTrait($modelClass)) {
                continue;
            }

            try {
                $instance = new $modelClass;
                $tableName = $instance->getTable();
                $results[] = [
                    'table' => $tableName,
                    'model' => class_basename($modelClass),
                    'class' => $modelClass,
                    'translation_file' => $instance->getTranslationFileName(),
                    'enabled' => $modelClass::isTranslatable(),
                ];
            } catch (\Throwable $e) {
                $modelName = class_basename($modelClass);
                $guessedTable = Str::plural(Str::snake($modelName));
                $results[] = [
                    'table' => $guessedTable,
                    'model' => $modelName,
                    'class' => $modelClass,
                    'translation_file' => Str::snake($modelName),
                    'enabled' => $modelClass::isTranslatable(),
                ];
            }
        }

        return $results;
    }

    public function getModelTables(): array
    {
        return $this->getTranslatableModels();
    }

    public function getMissingTableTranslations(): array
    {
        $modelTables = $this->getModelTables();
        $missing = [];

        foreach ($modelTables as $item) {
            if (!$item['enabled']) {
                continue;
            }

            $table = $item['table'];
            $translationFile = $item['translation_file'];

            $existsIn = [];
            $missingIn = [];

            foreach ($this->locales as $locale) {
                $files = $this->getTranslationFiles($locale);
                if (in_array($translationFile, $files)) {
                    $existsIn[] = $locale;
                } else {
                    $missingIn[] = $locale;
                }
            }

            if (!empty($missingIn)) {
                $missing[] = [
                    'table' => $table,
                    'model' => $item['model'],
                    'suggested_file' => $translationFile,
                    'exists_in' => $existsIn,
                    'missing_in' => $missingIn,
                ];
            }
        }

        return $missing;
    }

    public function getTranslationFiles(string $locale): array
    {
        $path = "{$this->langPath}/{$locale}";
        if (!File::isDirectory($path))
            return [];

        return collect(File::files($path))
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->map(fn($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->toArray();
    }

    public function getTranslationCompleteness(): array
    {
        $baseLocale = $this->locales[0] ?? 'en';
        $results = [];
        $baseFiles = $this->getTranslationFiles($baseLocale);

        foreach ($baseFiles as $fileName) {
            $baseTranslation = $this->loadTranslationFile($baseLocale, $fileName);
            $baseKeys = $this->flattenTranslationKeys($baseTranslation);
            $baseCount = count($baseKeys);

            foreach (array_slice($this->locales, 1) as $targetLocale) {
                $targetTranslation = $this->loadTranslationFile($targetLocale, $fileName);
                $targetKeys = $this->flattenTranslationKeys($targetTranslation);

                $missingKeys = array_diff($baseKeys, $targetKeys);
                $targetCount = count($targetKeys);
                $completeness = $baseCount > 0 ? round(($targetCount / $baseCount) * 100, 1) : 100;

                $results[$fileName . '/' . $targetLocale] = [
                    'base_locale' => $baseLocale,
                    'base_keys' => $baseCount,
                    'target_locale' => $targetLocale,
                    'target_keys' => $targetCount,
                    'completeness' => $completeness,
                    'missing_keys' => array_values($missingKeys),
                ];
            }
        }
        return $results;
    }

    public function getModelRelationships(): array
    {
        $models = $this->discoverModels();
        $results = [];

        foreach ($models as $modelClass) {
            if (!$this->usesTranslatableTrait($modelClass)) {
                continue;
            }

            $modelName = class_basename($modelClass);
            $relationships = $this->extractRelationships($modelClass);
            $fileName = Str::of($modelName)->snake()->toString() . '_relation';

            $existsIn = [];
            $missingIn = [];

            foreach ($this->locales as $locale) {
                $path = "{$this->langPath}/{$locale}/{$fileName}.php";
                if (File::exists($path)) {
                    $existsIn[] = $locale;
                } else {
                    $missingIn[] = $locale;
                }
            }

            $hasTranslation = count($existsIn) > 0;

            $results[] = [
                'model' => $modelName,
                'class' => $modelClass,
                'relationships' => $relationships,
                'has_translation' => $hasTranslation,
                'exists_in' => $existsIn,
                'missing_in' => $missingIn,
                'translation_file' => $fileName,
            ];
        }

        return $results;
    }

    public function getAiStatus(): array
    {
        $config = config('filament-translation-toolkit.ai', []);
        $apiKey = $config['api_key'] ?? '';
        $configured = !empty($apiKey);
        $preview = $configured ? substr($apiKey, 0, 8) . '...' . substr($apiKey, -4) : '';

        return [
            'configured' => $configured,
            'api_key_preview' => $preview,
            'model' => $config['model'] ?? 'openai/gpt-4o-mini',
            'endpoint' => $config['endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions',
        ];
    }

    public function getFileSummary(): array
    {
        $summary = [];
        $translatableModels = $this->getTranslatableModels();
        $validFileNames = collect($translatableModels)->map(fn($m) => $m['translation_file'])->toArray();

        $allFiles = [];
        foreach ($this->locales as $locale) {
            $allFiles = array_merge($allFiles, $this->getTranslationFiles($locale));
        }

        foreach (array_unique($allFiles) as $fileName) {
            if (!in_array($fileName, $validFileNames))
                continue;

            $locales = [];
            foreach ($this->locales as $locale) {
                $translation = $this->loadTranslationFile($locale, $fileName);
                $locales[$locale] = count($this->flattenTranslationKeys($translation));
            }
            $summary[] = ['file' => $fileName, 'locales' => $locales];
        }

        return $summary;
    }

    protected function discoverModels(): array
    {
        // قائمة النماذج المستبعدة
        $excludedModels = config('filament-translation-toolkit.excluded_models', ['Job', 'Migration', 'PasswordResetToken', 'CacheLock', 'FailedJob', 'JobBatch', 'Permission', 'Role']);
        //$modelsPath = base_path(str_replace('\\', '\\', $this->modelNamespace));

        // 1. احصل على المسار الأساسي من الإعدادات
        $namespace = $this->modelNamespace; // مثلاً 'App\Models'

        // 2. إذا كان يبدأ بـ 'App', قم بتحويله إلى 'app' ليتوافق مع نظام ملفات Laravel
        if (Str::startsWith($namespace, 'App')) {
            $path = app_path(Str::after($namespace, 'App\\'));
        } else {
            // إذا كان Namespace مخصصاً خارج مجلد app
            $path = base_path(str_replace('\\', '/', $namespace));
        }

        if (!File::isDirectory($path)) {
            return [];
        }

        $models = [];
        foreach (File::allFiles($path) as $file) {
            // ... بقية كود الاكتشاف
            $className = $namespace . '\\' . Str::replaceLast('.php', '', $file->getRelativePathname());
            $className = str_replace(['/', '\\'], '\\', $className);



            if (in_array(class_basename($className), $excludedModels))
                continue;

            if (class_exists($className) && is_subclass_of($className, Model::class) && !(new ReflectionClass($className))->isAbstract()) {
                $models[] = $className;
            }
        }
        sort($models);
        return $models;
    }

    protected function extractRelationships(string $modelClass): array
    {
        if (!class_exists($modelClass))
            return [];
        $reflection = new ReflectionClass($modelClass);
        $modelInstance = $reflection->newInstanceWithoutConstructor();
        $relationships = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0)
                continue;
            try {
                $result = $method->invoke($modelInstance);
                if ($result instanceof Relation)
                    $relationships[] = $method->getName();
            } catch (\Throwable $e) {
                continue;
            }
        }
        return $relationships;
    }

    /**
     * Find the Filament Resource class for a given model and extract navigation defaults.
     */
    public function getResourceDefaults(string $modelClass): ?array
    {
        $modelName = class_basename($modelClass);
        $resourceNamespace = config('filament-translation-toolkit.model_namespace', 'App\\Models');

        // Try to find resource in common Filament paths
        $possiblePaths = [
            "App\\Filament\\Resources\\{$modelName}Resource",
            "App\\Filament\\Resources",
        ];

        // Scan the Filament Resources directory
        $resourcesPath = app_path('Filament/Resources');
        if (!File::isDirectory($resourcesPath)) {
            return null;
        }

        $resourceClass = null;
        foreach (File::allFiles($resourcesPath) as $file) {
            $className = 'App\\Filament\\Resources\\' . Str::replaceLast('.php', '', $file->getRelativePathname());
            $className = str_replace(['/', '\\'], '\\', $className);

            if (
                class_exists($className)
                && str_ends_with($className, 'Resource')
                && !abstract_class_exists($className)
            ) {
                $reflection = new ReflectionClass($className);
                $getModelMethod = $reflection->getMethod('getModel');

                if ($getModelMethod) {
                    try {
                        $tempInstance = $reflection->newInstanceWithoutConstructor();
                        $resourceModel = $getModelMethod->invoke($tempInstance);

                        if ($resourceModel === $modelClass || class_basename($resourceModel) === $modelName) {
                            $resourceClass = $className;
                            break;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            }
        }

        if (!$resourceClass) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($resourceClass);
            $instance = $reflection->newInstanceWithoutConstructor();

            $navigationLabel = null;
            $navigationGroup = null;
            $navigationIcon = null;
            $navigationSort = null;
            $modelLabel = null;
            $pluralModelLabel = null;

            if ($reflection->hasMethod('getNavigationLabel')) {
                $navigationLabel = $resourceClass::getNavigationLabel();
            }
            if ($reflection->hasMethod('getNavigationGroup')) {
                $navigationGroup = $resourceClass::getNavigationGroup();
            }
            if ($reflection->hasMethod('getNavigationIcon')) {
                $navigationIcon = $resourceClass::getNavigationIcon();
            }
            if ($reflection->hasMethod('getNavigationSort')) {
                $navigationSort = $resourceClass::getNavigationSort();
            }
            if ($reflection->hasMethod('getModelLabel')) {
                $modelLabel = $resourceClass::getModelLabel();
            }
            if ($reflection->hasMethod('getPluralModelLabel')) {
                $pluralModelLabel = $resourceClass::getPluralModelLabel();
            }

            return [
                'navigation' => [
                    'label' => $pluralModelLabel ?? $navigationLabel ?? Str::title(str_replace('_', ' ', Str::plural(class_basename($modelClass)))),
                    'group' => $navigationGroup ?? Str::title(str_replace('_', ' ', Str::plural(class_basename($modelClass)))),
                    'model_label' => $modelLabel ?? Str::title(str_replace('_', ' ', class_basename($modelClass))),
                    'plural_label' => $pluralModelLabel ?? Str::title(str_replace('_', ' ', Str::plural(class_basename($modelClass)))),
                    'icon' => $navigationIcon ?? config('filament-translation-toolkit.default_icon', 'heroicon-m-building-office-2'),
                    'sort' => $navigationSort,
                ],
                'resource_class' => $resourceClass,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if a model class uses the Translatable trait.
     */
    protected function usesTranslatableTrait(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }

        $reflection = new ReflectionClass($modelClass);
        $uses = array_keys($reflection->getTraits());

        return in_array(Translatable::class, $uses, true);
    }

    protected function loadTranslationFile(string $locale, string $fileName): array
    {
        $path = "{$this->langPath}/{$locale}/{$fileName}.php";
        return (File::exists($path) && is_array($t = include $path)) ? $t : [];
    }

    protected function flattenTranslationKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value))
                $keys = array_merge($keys, $this->flattenTranslationKeys($value, $fullKey));
            else
                $keys[] = $fullKey;
        }
        return $keys;
    }
}