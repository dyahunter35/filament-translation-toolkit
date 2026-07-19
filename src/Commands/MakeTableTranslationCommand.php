<?php

namespace Dyahunter35\FilamentTranslationToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeTableTranslationCommand extends Command
{
    protected $signature = 'make:table-translation
                            {table : Table name}
                            {--lang= : Base language (defaults to first locale in config)}';

    protected $description = 'Generate a translation file for a database table';

    public function handle(): int
    {
        $table = $this->argument('table');
        $baseLang = $this->option('lang');
        $targetLangs = $baseLang ? [$baseLang] : config('filament-translation-toolkit.locales', ['en', 'ar']);
        $defaultIcon = config('filament-translation-toolkit.default_icon', 'heroicon-m-building-office-2');

        if (! Schema::hasTable($table)) {
            $this->error("Table [{$table}] does not exist!");

            return 1;
        }

        $columns = Schema::getColumnListing($table);
        $className = Str::studly(Str::singular($table));
        $fileName = Str::of(Str::singular($table))->snake();
        $pluralName = Str::plural($className);

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
            $content .= "        'group' => '".($lang === 'ar' ? $pluralName : Str::title(str_replace('_', ' ', Str::plural($table))))."',\n";
            $content .= "        'label' => '{$pluralName}',\n";
            $content .= "        'plural_label' => '{$pluralName}',\n";
            $content .= "        'model_label' => '{$className}',\n";
            $content .= "        'icon' => '{$defaultIcon}',\n";
            $content .= "    ],\n";
            $content .= "    'breadcrumbs' => [\n";
            $content .= "        'index' => '{$pluralName}',\n";
            $content .= "        'create' => 'Add {$className}',\n";
            $content .= "        'edit' => 'Edit {$className}',\n";
            $content .= "    ],\n";
            $content .= "    'fields' => [\n";
            foreach ($fieldsArray as $name => $field) {
                $content .= "        '{$name}' => [\n";
                $content .= "            'label' => '{$field['label']}',\n";
                $content .= "            'placeholder' => '{$field['placeholder']}',\n";
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
