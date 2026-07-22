<?php

namespace Dyahunter35\FilamentTranslationToolkit\Pages;

use Dyahunter35\FilamentTranslationToolkit\Services\AiTranslationService;
use Dyahunter35\FilamentTranslationToolkit\Services\TranslationScanner;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslationDashboard extends Page
{
    protected string $view = 'filament-translation-toolkit::pages.translation-dashboard';

    public static function getNavigationIcon(): ?string
    {
        return config('filament-translation-toolkit.navigation.icon', 'heroicon-o-language');
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-translation-toolkit.navigation.label')
            ?? __("filament-translation-toolkit::dashboard.navigation.label");
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-translation-toolkit.navigation.group')
            ?? __("filament-translation-toolkit::dashboard.navigation.group");
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-translation-toolkit.navigation.sort', 999);
    }

    public function getTitle(): string
    {
        return config('filament-translation-toolkit.navigation.title')
            ?? __("filament-translation-toolkit::dashboard.navigation.title");
    }

    public ?array $aiStatus = null;

    public ?array $missingTables = [];

    public ?array $completeness = [];

    public ?array $relationships = [];

    public ?array $fileSummary = [];

    public bool $isLoading = false;

    public bool $useResourceDefaults = false;

    public function mount(): void
    {
        $this->useResourceDefaults = config('filament-translation-toolkit.use_resource_defaults', true);
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $scanner = app(TranslationScanner::class);

        $this->aiStatus = $scanner->getAiStatus();
        $this->missingTables = $scanner->getMissingTableTranslations();
        $this->completeness = $scanner->getTranslationCompleteness();
        $this->relationships = $scanner->getModelRelationships();
        $this->fileSummary = $scanner->getFileSummary();
    }

    public function toggleResourceDefaults(): void
    {
        $this->useResourceDefaults = !$this->useResourceDefaults;
    }

    /**
     * Generate translation for a specific language.
     */
    public function generateTableTranslation(string $table, string $lang, ?string $translationFile = null): void
    {
        $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');
        $fileName = $translationFile ?? Str::of(Str::singular($table))->snake();
        $defaultIcon = config('filament-translation-toolkit.default_icon', 'heroicon-m-building-office-2');

        $className = Str::studly(Str::singular($table));
        $pluralName = Str::plural($className);

        // Get resource defaults if enabled
        $navigation = $this->getResourceDefaults($className, $table) ?? [
            'label' => $pluralName,
            'group' => $lang === 'ar' ? $pluralName : Str::title(str_replace('_', ' ', Str::plural($table))),
            'model_label' => $className,
            'plural_label' => $pluralName,
            'icon' => $defaultIcon,
        ];

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

        $fieldsArray = [];
        foreach ($columns as $column) {
            $fieldsArray[$column] = [
                'label' => $lang === 'ar' ? $column : Str::title(str_replace('_', ' ', $column)),
                'placeholder' => '',
            ];
        }

        $content = "<?php\nreturn [\n";
        $content .= "    'navigation' => [\n";
        $content .= "        'group' => '" . addslashes($navigation['group']) . "',\n";
        $content .= "        'label' => '" . addslashes($navigation['label']) . "',\n";
        $content .= "        'plural_label' => '" . addslashes($navigation['plural_label']) . "',\n";
        $content .= "        'model_label' => '" . addslashes($navigation['model_label']) . "',\n";
        $content .= "        'icon' => '" . addslashes($navigation['icon']) . "',\n";
        $content .= "    ],\n";
        $content .= "    'breadcrumbs' => [\n";
        $content .= "        'index' => '" . addslashes($navigation['plural_label']) . "',\n";
        $content .= "        'create' => 'Add " . addslashes($className) . "',\n";
        $content .= "        'edit' => 'Edit " . addslashes($className) . "',\n";
        $content .= "    ],\n";
        $content .= "    'fields' => [\n";
        foreach ($fieldsArray as $name => $field) {
            $content .= "        '{$name}' => [\n";
            $content .= "            'label' => '" . addslashes($field['label']) . "',\n";
            $content .= "            'placeholder' => '" . addslashes($field['placeholder']) . "',\n";
            $content .= "        ],\n";
        }
        $content .= "    ],\n";
        $content .= "];\n";

        $directory = "{$langPath}/{$lang}";
        File::ensureDirectoryExists($directory, 0755, true);

        $path = "{$directory}/{$fileName}.php";
        File::put($path, $content);

        $this->refreshData();

        Notification::make()
            ->title(__('filament-translation-toolkit::dashboard.notifications.generated_lang', ['lang' => $lang]))
            ->success()
            ->send();
    }

    /**
     * Generate translation for ALL languages at once.
     */
    public function generateTableTranslationAll(string $table, ?string $translationFile = null): void
    {
        $locales = config('filament-translation-toolkit.locales', ['en', 'ar']);

        foreach ($locales as $lang) {
            $this->generateTableTranslation($table, $lang, $translationFile);
        }

        $this->refreshData();

        Notification::make()
            ->title(__('filament-translation-toolkit::dashboard.notifications.generated'))
            ->success()
            ->send();
    }

    /**
     * AI generate for a specific language.
     */
    public function generateAiTableTranslation(string $table, ?string $lang = null): void
    {
        $params = [
            'table' => $table,
            '--ai' => true,
            '--type' => 'resource',
        ];

        if ($lang) {
            $params['--lang'] = $lang;
        }

        $exitCode = Artisan::call('make:translation', $params);

        $this->refreshData();

        if ($exitCode === 0) {
            Notification::make()
                ->title(__('filament-translation-toolkit::dashboard.notifications.ai_generated'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(Artisan::output())
                ->danger()
                ->send();
        }
    }

    public function generateMissingRelationTranslation(string $model): void
    {
        $tableName = Str::of(Str::snake(Str::plural($model)))->toString();

        $exitCode = Artisan::call('make:translation', [
            'table' => $tableName,
            '--type' => 'relation',
        ]);

        $this->refreshData();

        if ($exitCode === 0) {
            Notification::make()
                ->title(__('filament-translation-toolkit::dashboard.notifications.relation_generated'))
                ->success()
                ->send();
        }
    }

    /**
     * Generate relation translation for a specific language.
     */
    public function generateRelationTranslation(string $model, string $lang): void
    {
        $tableName = Str::of(Str::snake(Str::plural($model)))->toString();
        $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');
        $fileName = Str::of($model)->snake()->toString() . '_relation';
        $className = Str::studly($model);

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);

        $fieldsArray = [];
        foreach ($columns as $column) {
            $fieldsArray[$column] = [
                'label' => $lang === 'ar' ? $column : Str::title(str_replace('_', ' ', $column)),
                'placeholder' => '',
            ];
        }

        $content = "<?php\nreturn [\n";
        $content .= "    'label' => [\n";
        $content .= "        'plural' => '" . addslashes(Str::plural($className)) . "',\n";
        $content .= "        'single' => '" . addslashes($className) . "',\n";
        $content .= "    ],\n";
        $content .= "    'fields' => [\n";
        foreach ($fieldsArray as $name => $field) {
            $content .= "        '{$name}' => [\n";
            $content .= "            'label' => '" . addslashes($field['label']) . "',\n";
            $content .= "            'placeholder' => '" . addslashes($field['placeholder']) . "',\n";
            $content .= "        ],\n";
        }
        $content .= "    ],\n";
        $content .= "];\n";

        $directory = "{$langPath}/{$lang}";
        File::ensureDirectoryExists($directory, 0755, true);

        $path = "{$directory}/{$fileName}.php";
        File::put($path, $content);

        $this->refreshData();

        Notification::make()
            ->title(__('filament-translation-toolkit::dashboard.notifications.relation_generated_lang', ['lang' => $lang]))
            ->success()
            ->send();
    }

    /**
     * Generate relation translation for all languages.
     */
    public function generateRelationTranslationAll(string $model): void
    {
        $locales = config('filament-translation-toolkit.locales', ['en', 'ar']);

        foreach ($locales as $lang) {
            $this->generateRelationTranslation($model, $lang);
        }

        $this->refreshData();

        Notification::make()
            ->title(__('filament-translation-toolkit::dashboard.notifications.relation_generated'))
            ->success()
            ->send();
    }

    /**
     * Get resource defaults for a model class.
     */
    protected function getResourceDefaults(string $className, string $table): ?array
    {
        if (!$this->useResourceDefaults) {
            return null;
        }

        $scanner = app(TranslationScanner::class);
        $modelNamespace = config('filament-translation-toolkit.model_namespace', 'App\\Models');
        $modelClass = $modelNamespace . '\\' . $className;

        $defaults = $scanner->getResourceDefaults($modelClass);

        return $defaults['navigation'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('filament-translation-toolkit::dashboard.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->refreshData()),
        ];
    }
}
