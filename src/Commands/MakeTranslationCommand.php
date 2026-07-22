<?php

namespace Dyahunter35\FilamentTranslationToolkit\Commands;

use Dyahunter35\FilamentTranslationToolkit\Services\AiTranslationService;
use Dyahunter35\FilamentTranslationToolkit\Services\TranslationScanner;
use Dyahunter35\FilamentTranslationToolkit\Templates\BaseTranslationTemplate;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeTranslationCommand extends Command
{
    protected $signature = 'make:translation
                            {table : Table name (or page class name for --type=page)}
                            {--ai : Use AI to generate smart translations}
                            {--type=resource : Template type (resource, relation, or page)}
                            {--lang= : Specific language (defaults to all)}
                            {--use-resource-defaults : Use navigation defaults from Filament Resource}';

    protected $description = 'Generate translation file for a database table, relation, or Filament page (basic or AI-powered)';

    public function handle(AiTranslationService $aiService): int
    {
        $table = $this->argument('table');
        $useAi = $this->option('ai');
        $type = $this->option('type');
        $baseLang = $this->option('lang');
        $targetLangs = $baseLang ? [$baseLang] : config('filament-translation-toolkit.locales', ['en', 'ar']);
        $useResourceDefaults = $this->option('use-resource-defaults') || config('filament-translation-toolkit.use_resource_defaults', true);

        if ($type === 'page') {
            return $this->handlePage($aiService, $table, $useAi, $targetLangs);
        }

        if (!Schema::hasTable($table)) {
            $this->error("Table [{$table}] does not exist!");
            return 1;
        }

        $columns = Schema::getColumnListing($table);
        $className = Str::studly(Str::singular($table));
        $fileName = Str::of(Str::singular($table))->snake();
        $pluralName = Str::plural($className);
        $defaultIcon = config('filament-translation-toolkit.default_icon', 'heroicon-m-building-office-2');

        $resourceDefaults = null;
        if ($useResourceDefaults) {
            $scanner = app(TranslationScanner::class);
            $modelNamespace = config('filament-translation-toolkit.model_namespace', 'App\\Models');
            $modelClass = $modelNamespace . '\\' . $className;
            $defaults = $scanner->getResourceDefaults($modelClass);
            $resourceDefaults = $defaults['navigation'] ?? null;
        }

        $fallbackNavigation = [
            'label' => $pluralName,
            'group' => Str::title(str_replace('_', ' ', Str::plural($table))),
            'model_label' => $className,
            'plural_label' => $pluralName,
            'icon' => $defaultIcon,
        ];

        $navigation = $resourceDefaults ?? $fallbackNavigation;

        if ($useAi) {
            return $this->generateAi($aiService, $table, $type, $targetLangs, $className, $fileName, $columns, $navigation);
        }

        return $this->generateBasic($table, $targetLangs, $className, $fileName, $pluralName, $columns, $navigation);
    }

    protected function handlePage(AiTranslationService $aiService, string $pageName, bool $useAi, array $targetLangs): int
    {
        $className = 'App\\Filament\\Pages\\' . Str::replaceLast('.php', '', $pageName);
        $className = str_replace(['/', '\\'], '\\', $className);

        if (!class_exists($className)) {
            $this->error("Page class [{$className}] does not exist!");
            return 1;
        }

        $snakeName = Str::of(class_basename($className))->snake()->toString();
        $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');

        $pageData = $this->extractPageDefaults($className);

        // Ensure English file exists
        $englishPath = "{$langPath}/en/{$snakeName}.php";
        if (!File::exists($englishPath)) {
            $this->generateBasicPage($snakeName, $pageData, $langPath, ['en']);
        }

        if ($useAi) {
            return $this->generateAiPage($aiService, $snakeName, $className, $pageData, $targetLangs, $langPath);
        }

        return $this->generateBasicPage($snakeName, $pageData, $langPath, $targetLangs);
    }

    public function extractPageDefaults(string $className): array
    {
        $pageName = class_basename($className);
        $heading = Str::title(str_replace('_', ' ', Str::snake($pageName)));
        $reflection = new \ReflectionClass($className);

        // Extract title
        if ($reflection->hasMethod('getTitle')) {
            try {
                $instance = $reflection->newInstanceWithoutConstructor();
                $title = $instance->getTitle();
                if (is_string($title) && !empty($title)) {
                    $heading = $title;
                }
            } catch (\Throwable $e) {}
        }

        // Extract navigation properties
        $navigationLabel = null;
        $navigationGroup = null;
        $navigationIcon = null;
        $navigationSort = null;

        if ($reflection->hasMethod('getNavigationLabel')) {
            try {
                $navigationLabel = $className::getNavigationLabel();
            } catch (\Throwable $e) {}
        }

        if ($reflection->hasMethod('getNavigationGroup')) {
            try {
                $navigationGroup = $className::getNavigationGroup();
            } catch (\Throwable $e) {}
        }

        if ($reflection->hasMethod('getNavigationIcon')) {
            try {
                $icon = $className::getNavigationIcon();
                if ($icon instanceof \BackedEnum) {
                    $icon = 'heroicon-' . $icon->value;
                } elseif (is_object($icon)) {
                    $icon = (string) $icon;
                }
                $navigationIcon = $icon;
            } catch (\Throwable $e) {}
        }

        if ($reflection->hasMethod('getNavigationSort')) {
            try {
                $navigationSort = $className::getNavigationSort();
            } catch (\Throwable $e) {}
        }

        return [
            'title' => $heading,
            'heading' => $heading,
            'navigation' => [
                'label' => $navigationLabel ?? $heading,
                'group' => $navigationGroup ?? '',
                'icon' => $navigationIcon ?? '',
                'sort' => $navigationSort,
            ],
            'breadcrumbs' => [
                'index' => $navigationLabel ?? $heading,
            ],
            'actions' => [
                'save' => 'Save',
                'cancel' => 'Cancel',
                'create' => 'Create',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'view' => 'View',
            ],
            'messages' => [
                'saved' => 'Saved successfully',
                'deleted' => 'Deleted successfully',
                'not_found' => 'Record not found',
            ],
        ];
    }

    protected function generateBasicPage(string $snakeName, array $pageData, string $langPath, array $targetLangs): int
    {
        foreach ($targetLangs as $lang) {
            $content = "<?php\n\nreturn [\n";
            $content .= "    'title' => '" . addslashes($pageData['title']) . "',\n";
            $content .= "    'heading' => '" . addslashes($pageData['heading']) . "',\n";
            $content .= "    'navigation' => [\n";
            $content .= "        'label' => '" . addslashes($pageData['navigation']['label']) . "',\n";
            $content .= "        'group' => '" . addslashes($pageData['navigation']['group']) . "',\n";
            if (!empty($pageData['navigation']['icon'])) {
                $content .= "        'icon' => '" . addslashes((string) $pageData['navigation']['icon']) . "',\n";
            }
            if (isset($pageData['navigation']['sort'])) {
                $content .= "        'sort' => " . intval($pageData['navigation']['sort']) . ",\n";
            }
            $content .= "    ],\n";
            $content .= "    'breadcrumbs' => [\n";
            $content .= "        'index' => '" . addslashes($pageData['breadcrumbs']['index']) . "',\n";
            $content .= "    ],\n";
            $content .= "    'actions' => [\n";
            foreach ($pageData['actions'] as $key => $value) {
                $content .= "        '" . addslashes($key) . "' => '" . addslashes($value) . "',\n";
            }
            $content .= "    ],\n";
            $content .= "    'messages' => [\n";
            foreach ($pageData['messages'] as $key => $value) {
                $content .= "        '" . addslashes($key) . "' => '" . addslashes($value) . "',\n";
            }
            $content .= "    ],\n";
            $content .= "];\n";

            $directory = "{$langPath}/{$lang}";
            File::ensureDirectoryExists($directory, 0755, true);

            $path = "{$directory}/{$snakeName}.php";
            File::put($path, $content);
            $this->info("Created: {$path}");
        }

        return 0;
    }

    protected function generateAiPage(AiTranslationService $aiService, string $snakeName, string $className, array $pageData, array $targetLangs, string $langPath): int
    {
        $englishPath = "{$langPath}/en/{$snakeName}.php";
        $englishData = require $englishPath;

        $flatEnglish = [
            'title' => $englishData['title'] ?? $pageData['title'],
            'heading' => $englishData['heading'] ?? $pageData['heading'],
            'subheading' => $englishData['subheading'] ?? '',
            'navigation' => $englishData['navigation'] ?? $pageData['navigation'],
            'breadcrumbs' => $englishData['breadcrumbs'] ?? $pageData['breadcrumbs'],
            'actions' => $englishData['actions'] ?? $pageData['actions'],
            'messages' => $englishData['messages'] ?? $pageData['messages'],
        ];

        $nonEnglishLangs = array_values(array_filter($targetLangs, fn ($l) => $l !== 'en'));

        if (empty($nonEnglishLangs)) {
            $this->info("Only English requested. Done.");
            return 0;
        }

        $this->info("Translating page English → " . implode(', ', $nonEnglishLangs) . " via AI...");

        try {
            $translations = $aiService->translateFromEnglish($flatEnglish, $nonEnglishLangs, $snakeName);
        } catch (\Exception $e) {
            $this->error('AI Exception: ' . $e->getMessage());
            return 1;
        }

        $template = new \Dyahunter35\FilamentTranslationToolkit\Templates\PageTranslationTemplate();

        foreach ($nonEnglishLangs as $lang) {
            $langData = $translations[$lang] ?? null;
            if (!$langData) {
                $this->warn("Missing AI translation for: {$lang}. Skipping...");
                continue;
            }

            // Preserve navigation icon/sort from original (don't translate icons)
            $langData['navigation']['icon'] = $pageData['navigation']['icon'] ?? '';
            if (isset($pageData['navigation']['sort'])) {
                $langData['navigation']['sort'] = $pageData['navigation']['sort'];
            }

            $content = "<?php\n\nreturn [\n";
            $content .= $template->build($langData);
            $content .= "];\n";

            $directory = "{$langPath}/{$lang}";
            File::ensureDirectoryExists($directory, 0755, true);

            $path = "{$directory}/{$snakeName}.php";
            File::put($path, $content);
            $this->info("Created: {$path}");
        }

        return 0;
    }

    protected function generateBasic(
        string $table,
        array $targetLangs,
        string $className,
        string $fileName,
        string $pluralName,
        array $columns,
        array $navigation
    ): int {
        $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');

        foreach ($targetLangs as $lang) {
            $fieldsArray = [];
            foreach ($columns as $column) {
                $fieldsArray[$column] = [
                    'label' => $lang === 'ar' ? $column : Str::title(str_replace('_', ' ', $column)),
                    'placeholder' => '',
                ];
            }

            $content = "<?php\nreturn [\n";
            $content .= "    'navigation' => [\n";
            $content .= "        'group' => '".addslashes($navigation['group'])."',\n";
            $content .= "        'label' => '".addslashes($navigation['label'])."',\n";
            $content .= "        'plural_label' => '".addslashes($navigation['plural_label'])."',\n";
            $content .= "        'model_label' => '".addslashes($navigation['model_label'])."',\n";
            $content .= "        'icon' => '".addslashes((string) $navigation['icon'])."',\n";
            $content .= "    ],\n";
            $content .= "    'breadcrumbs' => [\n";
            $content .= "        'index' => '".addslashes($navigation['plural_label'])."',\n";
            $content .= "        'create' => 'Add ".addslashes($className)."',\n";
            $content .= "        'edit' => 'Edit ".addslashes($className)."',\n";
            $content .= "    ],\n";
            $content .= "    'fields' => [\n";
            foreach ($fieldsArray as $name => $field) {
                $content .= "        '{$name}' => [\n";
                $content .= "            'label' => '".addslashes($field['label'])."',\n";
                $content .= "            'placeholder' => '".addslashes($field['placeholder'])."',\n";
                $content .= "        ],\n";
            }
            $content .= "    ],\n";
            $content .= "];\n";

            $directory = "{$langPath}/{$lang}";
            File::ensureDirectoryExists($directory, 0755, true);

            $path = "{$directory}/{$fileName}.php";
            File::put($path, $content);
            $this->info("Created: {$path}");
        }

        return 0;
    }

    protected function generateAi(
        AiTranslationService $aiService,
        string $table,
        string $type,
        array $targetLangs,
        string $className,
        string $fileName,
        array $columns,
        array $navigation
    ): int {
        $templateClass = '\\Dyahunter35\\FilamentTranslationToolkit\\Templates\\'.Str::studly($type).'Template';

        if (!class_exists($templateClass)) {
            $this->error("Template type [{$type}] does not exist! Expected: {$templateClass}");
            return 1;
        }

        /** @var BaseTranslationTemplate $templateInstance */
        $templateInstance = new $templateClass();
        $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');
        $finalFileName = $type === 'relation' ? "{$fileName}_relation" : $fileName;

        $englishPath = "{$langPath}/en/{$finalFileName}.php";
        $englishData = null;

        if (File::exists($englishPath)) {
            $this->info("English file found: {$englishPath}");
            $englishData = require $englishPath;
        } else {
            $this->warn("English file not found. Generating basic English translation first...");

            $this->generateBasic(
                $table,
                ['en'],
                $className,
                $fileName,
                Str::plural($className),
                $columns,
                $navigation
            );

            $englishData = require $englishPath;
            $this->info("Basic English file created: {$englishPath}");
        }

        $flatEnglish = $this->flattenEnglishData($englishData, $navigation);

        $nonEnglishLangs = array_values(array_filter($targetLangs, fn ($l) => $l !== 'en'));

        if (empty($nonEnglishLangs)) {
            $this->info("Only English requested. Done.");
            return 0;
        }

        $this->info("Translating English → " . implode(', ', $nonEnglishLangs) . " via AI...");

        try {
            $translations = $aiService->translateFromEnglish($flatEnglish, $nonEnglishLangs, $table);

            if (!$translations) {
                $this->error('Failed to parse AI translation response.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('AI Exception: ' . $e->getMessage());
            return 1;
        }

        foreach ($nonEnglishLangs as $lang) {
            $langData = $translations[$lang] ?? null;
            if (!$langData) {
                $this->warn("Missing AI translation for language: {$lang}. Skipping...");
                continue;
            }

            $content = "<?php\n\nreturn [\n";
            $content .= $templateInstance->build($langData);
            $content .= "];\n";

            $directory = "{$langPath}/{$lang}";
            File::ensureDirectoryExists($directory, 0755, true);

            $path = "{$directory}/{$finalFileName}.php";
            File::put($path, $content);
            $this->info("Created: {$path}");
        }

        return 0;
    }

    private function extractRelationships(string $modelName): array
    {
        $modelNamespace = config('filament-translation-toolkit.model_namespace', 'App\\Models');
        $modelClass = $modelNamespace . '\\' . $modelName;

        if (!class_exists($modelClass)) {
            return [];
        }

        $relationships = [];
        $reflection = new \ReflectionClass($modelClass);
        $modelInstance = app($modelClass);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                if ($method->invoke($modelInstance) instanceof Relation) {
                    $relationships[] = $method->getName();
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $relationships;
    }

    private function flattenEnglishData(array $englishData, array $navigation): array
    {
        $nav = $englishData['navigation'] ?? $navigation;

        return [
            'group' => $nav['group'] ?? $navigation['group'] ?? '',
            'label' => $nav['label'] ?? $navigation['label'] ?? '',
            'plural_label' => $nav['plural_label'] ?? $navigation['plural_label'] ?? '',
            'model_label' => $nav['model_label'] ?? $navigation['model_label'] ?? '',
            'icon' => $nav['icon'] ?? $navigation['icon'] ?? '',
            'breadcrumbs' => $englishData['breadcrumbs'] ?? [],
            'fields' => $englishData['fields'] ?? [],
            'filters' => $englishData['filters'] ?? [],
            'actions' => $englishData['actions'] ?? [],
            'label_single' => $englishData['label']['single'] ?? $englishData['label_single'] ?? '',
            'label_plural' => $englishData['label']['plural'] ?? $englishData['label_plural'] ?? '',
        ];
    }
}
