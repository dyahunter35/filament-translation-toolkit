<?php

namespace Dyahunter35\FilamentTranslationToolkit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
     * Get all database tables.
     *
     * @return array<int, string>
     */
    public function getAllTables(): array
    {
        return Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
    }

    /**
     * Get all translation file names (without extension) for a given locale.
     *
     * @return array<int, string>
     */
    public function getTranslationFiles(string $locale): array
    {
        $path = "{$this->langPath}/{$locale}";

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->toArray();
    }

    /**
     * Find tables that are missing translation files in all locales.
     *
     * @return array<int, array{table: string, suggested_file: string, exists_in: array<int, string>, missing_in: array<int, string>}>
     */
    public function getMissingTableTranslations(): array
    {
        $tables = $this->getAllTables();
        $missing = [];

        foreach ($tables as $table) {
            $fileName = Str::of(Str::singular($table))->snake()->toString();
            $existsIn = [];
            $missingIn = [];

            foreach ($this->locales as $locale) {
                $files = $this->getTranslationFiles($locale);
                if (in_array($fileName, $files)) {
                    $existsIn[] = $locale;
                } else {
                    $missingIn[] = $locale;
                }
            }

            if (! empty($missingIn)) {
                $missing[] = [
                    'table' => $table,
                    'suggested_file' => $fileName,
                    'exists_in' => $existsIn,
                    'missing_in' => $missingIn,
                ];
            }
        }

        return $missing;
    }

    /**
     * Get translation completeness between the base locale and other locales.
     *
     * @return array<string, array{base_locale: string, base_keys: int, target_locale: string, target_keys: int, completeness: float, missing_keys: array<int, string>}>
     */
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

                $results[$fileName.'/'.$targetLocale] = [
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

    /**
     * Find all Eloquent models and their relationships.
     *
     * @return array<int, array{model: string, class: string, relationships: array<int, string>, has_translation: bool, translation_file: string|null}>
     */
    public function getModelRelationships(): array
    {
        $models = $this->discoverModels();
        $results = [];

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);
            $relationships = $this->extractRelationships($modelClass);
            $fileName = Str::of($modelName)->snake()->toString().'_relation';

            $hasTranslation = false;
            $translationFile = null;

            foreach ($this->locales as $locale) {
                $path = "{$this->langPath}/{$locale}/{$fileName}.php";
                if (File::exists($path)) {
                    $hasTranslation = true;
                    $translationFile = "{$locale}/{$fileName}.php";
                    break;
                }
            }

            $results[] = [
                'model' => $modelName,
                'class' => $modelClass,
                'relationships' => $relationships,
                'has_translation' => $hasTranslation,
                'translation_file' => $translationFile,
            ];
        }

        return $results;
    }

    /**
     * Get AI service status.
     *
     * @return array{configured: bool, api_key_preview: string, model: string, endpoint: string}
     */
    public function getAiStatus(): array
    {
        $config = config('filament-translation-toolkit.ai', []);
        $apiKey = $config['api_key'] ?? '';
        $configured = ! empty($apiKey);

        $preview = $configured
            ? substr($apiKey, 0, 8).'...'.substr($apiKey, -4)
            : '';

        return [
            'configured' => $configured,
            'api_key_preview' => $preview,
            'model' => $config['model'] ?? 'openai/gpt-4o-mini',
            'endpoint' => $config['endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions',
        ];
    }

    /**
     * Get a summary of all translation files with their key counts.
     *
     * @return array<int, array{file: string, locales: array<string, int>}>
     */
    public function getFileSummary(): array
    {
        $summary = [];
        $allFiles = [];

        foreach ($this->locales as $locale) {
            $files = $this->getTranslationFiles($locale);
            $allFiles = array_merge($allFiles, $files);
        }

        $allFiles = array_unique($allFiles);
        sort($allFiles);

        foreach ($allFiles as $fileName) {
            $locales = [];
            foreach ($this->locales as $locale) {
                $translation = $this->loadTranslationFile($locale, $fileName);
                $locales[$locale] = count($this->flattenTranslationKeys($translation));
            }

            $summary[] = [
                'file' => $fileName,
                'locales' => $locales,
            ];
        }

        return $summary;
    }

    /**
     * Discover all Eloquent models in the configured namespace.
     *
     * @return array<int, class-string<Model>>
     */
    protected function discoverModels(): array
    {
        $namespacePath = str_replace('\\', '/', $this->modelNamespace);
        $modelsPath = app_path($namespacePath);

        if (! File::isDirectory($modelsPath)) {
            return [];
        }

        $models = [];

        foreach (File::allFiles($modelsPath) as $file) {
            $relativePath = $file->getRelativePathname();
            $className = $this->modelNamespace.'\\'.Str::replaceLast('.php', '', $relativePath);

            // Convert directory separators to namespace separators
            $className = str_replace('/', '\\', $className);

            if (is_subclass_of($className, Model::class) && ! (new \ReflectionClass($className))->isAbstract()) {
                $models[] = $className;
            }
        }

        sort($models);

        return $models;
    }

    /**
     * Extract Eloquent relationship method names from a model.
     *
     * @return array<int, string>
     */
    protected function extractRelationships(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        $relationships = [];
        $reflection = new \ReflectionClass($modelClass);

        try {
            $modelInstance = app($modelClass);
        } catch (\Exception $e) {
            return [];
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                $result = $method->invoke($modelInstance);
                if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    $relationships[] = $method->getName();
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Load a translation file and return its contents.
     *
     * @return array<string, mixed>
     */
    protected function loadTranslationFile(string $locale, string $fileName): array
    {
        $path = "{$this->langPath}/{$locale}/{$fileName}.php";

        if (! File::exists($path)) {
            return [];
        }

        $translation = include $path;

        return is_array($translation) ? $translation : [];
    }

    /**
     * Flatten a nested translation array into dot-notation keys.
     *
     * @param  array<string, mixed>  $array
     * @return array<int, string>
     */
    protected function flattenTranslationKeys(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenTranslationKeys($value, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }

        return $keys;
    }
}
