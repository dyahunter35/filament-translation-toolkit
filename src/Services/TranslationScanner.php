<?php

namespace Dyahunter35\FilamentTranslationToolkit\Services;

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

    public function getModelTables(): array
    {
        $models = $this->discoverModels();
        $results = [];

        foreach ($models as $modelClass) {
            try {
                $instance = new $modelClass;
                $tableName = $instance->getTable();
                $results[] = [
                    'table' => $tableName,
                    'model' => class_basename($modelClass),
                    'class' => $modelClass,
                ];
            } catch (\Throwable $e) {
                $modelName = class_basename($modelClass);
                $guessedTable = Str::plural(Str::snake($modelName));
                $results[] = [
                    'table' => $guessedTable,
                    'model' => $modelName,
                    'class' => $modelClass,
                ];
            }
        }
        return $results;
    }

    public function getMissingTableTranslations(): array
    {
        $modelTables = $this->getModelTables();
        $missing = [];

        foreach ($modelTables as $item) {
            $table = $item['table'];
            $singularName = Str::singular($table);
            $pluralName = $table;

            $existsIn = [];
            $missingIn = [];

            foreach ($this->locales as $locale) {
                $files = $this->getTranslationFiles($locale);
                // التحقق من وجود الملف باسم المفرد أو الجمع
                if (in_array($singularName, $files) || in_array($pluralName, $files)) {
                    $existsIn[] = $locale;
                } else {
                    $missingIn[] = $locale;
                }
            }

            if (!empty($missingIn)) {
                $missing[] = [
                    'table' => $table,
                    'model' => $item['model'],
                    'suggested_file' => $singularName,
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
            $modelName = class_basename($modelClass);
            $relationships = $this->extractRelationships($modelClass);
            $fileName = Str::of($modelName)->snake()->toString() . '_relation';

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
        $models = $this->discoverModels();
        $validFileNames = collect($models)->map(fn($m) => Str::snake(class_basename($m)))->toArray();

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