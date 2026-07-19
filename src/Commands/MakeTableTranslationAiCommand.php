<?php

namespace Dyahunter35\FilamentTranslationToolkit\Commands;

use Dyahunter35\FilamentTranslationToolkit\Services\AiTranslationService;
use Dyahunter35\FilamentTranslationToolkit\Templates\BaseTranslationTemplate;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeTableTranslationAiCommand extends Command
{
    protected $signature = 'make:ai-translation
                            {table : Table name}
                            {--type=resource : The template type (e.g. resource, relation)}';

    protected $description = 'Generate an AI-powered translation file using templates and AI service';

    public function handle(AiTranslationService $aiService): int
    {
        $table = $this->argument('table');
        $type = $this->option('type');
        $targetLangs = config('filament-translation-toolkit.locales', ['en', 'ar']);

        $templateClass = '\\Dyahunter35\\FilamentTranslationToolkit\\Templates\\'.Str::studly($type).'Template';

        if (! class_exists($templateClass)) {
            $this->error("Template type [{$type}] does not exist! Expected: {$templateClass}");

            return 1;
        }

        if (! Schema::hasTable($table)) {
            $this->error("Table [{$table}] does not exist!");

            return 1;
        }

        /** @var BaseTranslationTemplate $templateInstance */
        $templateInstance = new $templateClass();

        $columns = Schema::getColumnListing($table);
        $className = Str::studly(Str::singular($table));
        $fileName = Str::of(Str::singular($table))->snake();
        $relationships = $this->extractRelationships($className);

        $this->info("Fetching AI translations for table: {$table} using [{$type}] template...");

        $schemaData = [
            'table_name' => $table,
            'model_name' => $className,
            'fields_to_translate' => array_unique(array_merge($columns, $relationships)),
            'template_type' => $type,
        ];

        try {
            $translations = $aiService->generateTranslation(
                $schemaData,
                $type,
                $templateInstance->getJsonStructure()
            );

            if (! $translations) {
                $this->error('Failed to parse JSON from AI response.');

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Exception: '.$e->getMessage());

            return 1;
        }

        foreach ($targetLangs as $lang) {
            $langData = $translations[$lang] ?? null;
            if (! $langData) {
                $this->warn("Missing AI translation for language: {$lang}. Skipping...");

                continue;
            }

            $content = "<?php\n\nreturn [\n";
            $content .= $templateInstance->build($langData);
            $content .= "];\n";

            $finalFileName = $type === 'relation' ? "{$fileName}_relation" : $fileName;

            $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');
            $directory = "{$langPath}/{$lang}";
            File::ensureDirectoryExists($directory, 0755, true);

            $path = "{$directory}/{$finalFileName}.php";
            File::put($path, $content);
            $this->info("Translation file created: {$path}");
        }

        return 0;
    }

    /**
     * Extract Eloquent relationship method names from a model via reflection.
     *
     * @return array<int, string>
     */
    private function extractRelationships(string $modelName): array
    {
        $modelNamespace = config('filament-translation-toolkit.model_namespace', 'App\\Models');
        $modelClass = $modelNamespace.'\\'.$modelName;

        if (! class_exists($modelClass)) {
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
