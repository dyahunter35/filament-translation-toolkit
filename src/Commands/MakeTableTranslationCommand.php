<?php

namespace Dyahunter35\FilamentTranslationToolkit\Commands;

use Dyahunter35\FilamentTranslationToolkit\Services\TranslationScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeTableTranslationCommand extends Command
{
    protected $signature = 'make:table-translation
                            {table : Table name}
                            {--lang= : Specific language to generate (defaults to all)}
                            {--use-resource-defaults : Use navigation defaults from Filament Resource class}';

    protected $description = 'Generate a translation file for a database table';

    public function handle(): int
    {
        $table = $this->argument('table');
        $baseLang = $this->option('lang');
        $targetLangs = $baseLang ? [$baseLang] : config('filament-translation-toolkit.locales', ['en', 'ar']);
        $defaultIcon = config('filament-translation-toolkit.default_icon', 'heroicon-m-building-office-2');
        $useResourceDefaults = $this->option('use-resource-defaults') || config('filament-translation-toolkit.use_resource_defaults', true);

        if (! Schema::hasTable($table)) {
            $this->error("Table [{$table}] does not exist!");

            return 1;
        }

        $columns = Schema::getColumnListing($table);
        $className = Str::studly(Str::singular($table));
        $fileName = Str::of(Str::singular($table))->snake();
        $pluralName = Str::plural($className);

        // Get resource defaults if enabled
        $navigation = null;
        if ($useResourceDefaults) {
            $scanner = app(TranslationScanner::class);
            $modelNamespace = config('filament-translation-toolkit.model_namespace', 'App\\Models');
            $modelClass = $modelNamespace . '\\' . $className;
            $defaults = $scanner->getResourceDefaults($modelClass);
            $navigation = $defaults['navigation'] ?? null;
        }

        $navigation = $navigation ?? [
            'label' => $pluralName,
            'group' => Str::title(str_replace('_', ' ', Str::plural($table))),
            'model_label' => $className,
            'plural_label' => $pluralName,
            'icon' => $defaultIcon,
        ];

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

            $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');
            $directory = "{$langPath}/{$lang}";
            File::ensureDirectoryExists($directory, 0755, true);

            $path = "{$directory}/{$fileName}.php";
            File::put($path, $content);
            $this->info("Translation file created: {$path}");
        }

        return 0;
    }
}
