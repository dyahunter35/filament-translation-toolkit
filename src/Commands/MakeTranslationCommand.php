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
                            {table : Table name}
                            {--ai : Use AI to generate smart translations}
                            {--type=resource : Template type (resource or relation)}
                            {--lang= : Specific language (defaults to all)}
                            {--use-resource-defaults : Use navigation defaults from Filament Resource}';

    protected $description = 'Generate translation file for a database table (basic or AI-powered)';

    public function handle(AiTranslationService $aiService): int
    {
        $table = $this->argument('table');
        $useAi = $this->option('ai');
        $type = $this->option('type');
        $baseLang = $this->option('lang');
        $targetLangs = $baseLang ? [$baseLang] : config('filament-translation-toolkit.locales', ['en', 'ar']);
        $useResourceDefaults = $this->option('use-resource-defaults') || config('filament-translation-toolkit.use_resource_defaults', true);

        if (!Schema::hasTable($table)) {
            $this->error("Table [{$table}] does not exist!");
            return 1;
        }

        $columns = Schema::getColumnListing($table);
        $className = Str::studly(Str::singular($table));
        $fileName = Str::of(Str::singular($table))->snake();
        $pluralName = Str::plural($className);
        $defaultIcon = config('filament-translation-toolkit.default_icon', 'heroicon-m-building-office-2');

        // Get resource defaults
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

        // AI mode
        if ($useAi) {
            return $this->generateAi($aiService, $table, $type, $targetLangs, $className, $fileName, $columns, $navigation);
        }

        // Basic mode
        return $this->generateBasic($table, $targetLangs, $className, $fileName, $pluralName, $columns, $navigation);
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
            $content .= "        'icon' => '".addslashes($navigation['icon'])."',\n";
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
        $relationships = $this->extractRelationships($className);

        $this->info("Fetching AI translations for table: {$table} using [{$type}] template...");

        $schemaData = [
            'table_name' => $table,
            'model_name' => $className,
            'fields_to_translate' => array_unique(array_merge($columns, $relationships)),
            'template_type' => $type,
            'resource_defaults' => $navigation,
        ];

        try {
            $translations = $aiService->generateTranslation(
                $schemaData,
                $type,
                $templateInstance->getJsonStructure()
            );

            if (!$translations) {
                $this->error('Failed to parse JSON from AI response.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            return 1;
        }

        $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');

        foreach ($targetLangs as $lang) {
            $langData = $translations[$lang] ?? null;
            if (!$langData) {
                $this->warn("Missing AI translation for language: {$lang}. Skipping...");
                continue;
            }

            if ($type === 'resource') {
                $langData['navigation'] = array_merge(
                    $langData['navigation'] ?? [],
                    $navigation
                );
            }

            $content = "<?php\n\nreturn [\n";
            $content .= $templateInstance->build($langData);
            $content .= "];\n";

            $finalFileName = $type === 'relation' ? "{$fileName}_relation" : $fileName;

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
}
