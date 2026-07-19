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
    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?string $navigationLabel = null;

    protected static \UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = null;

    protected static ?string $title = null;

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

    public static function getTitle(): string
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

    public function mount(): void
    {
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

    public function generateTableTranslation(string $table): void
    {
        $langPath = config('filament-translation-toolkit.lang_path') ?? base_path('lang');
        $fileName = Str::of(Str::singular($table))->snake();
        $locales = config('filament-translation-toolkit.locales', ['en', 'ar']);
        $defaultIcon = config('filament-translation-toolkit.default_icon', 'heroicon-m-building-office-2');

        $className = Str::studly(Str::singular($table));
        $pluralName = Str::plural($className);

        foreach ($locales as $lang) {
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

            $directory = "{$langPath}/{$lang}";
            File::ensureDirectoryExists($directory, 0755, true);

            $path = "{$directory}/{$fileName}.php";
            File::put($path, $content);
        }

        $this->refreshData();

        Notification::make()
            ->title(__('filament-translation-toolkit::dashboard.notifications.generated'))
            ->success()
            ->send();
    }

    public function generateAiTableTranslation(string $table): void
    {
        $exitCode = Artisan::call('make:ai-translation', [
            'table' => $table,
            '--type' => 'resource',
        ]);

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

        $exitCode = Artisan::call('make:table-translation', [
            'table' => $tableName,
        ]);

        $this->refreshData();

        if ($exitCode === 0) {
            Notification::make()
                ->title(__('filament-translation-toolkit::dashboard.notifications.relation_generated'))
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('filament-translation-toolkit::dashboard.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refreshData()),
        ];
    }
}
