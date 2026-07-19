<?php

namespace Alsultan\FilamentTranslationToolkit\Pages;

use Alsultan\FilamentTranslationToolkit\Services\AiTranslationService;
use Alsultan\FilamentTranslationToolkit\Services\TranslationScanner;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslationDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationLabel = 'Translation Dashboard';

    protected static ?string $navigationGroup = 'Toolkit';

    protected static ?int $navigationSort = 999;

    protected static ?string $title = 'Translation Dashboard';

    protected string $view = 'filament-translation-toolkit::pages.translation-dashboard';

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

    public function generateTableTranslation(string $table, string $type = 'resource'): Action
    {
        return Action::make('generate_'.$table)
            ->label(__('filament-translation-toolkit::dashboard.actions.generate'))
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading(__('filament-translation-toolkit::dashboard.actions.generate_confirm_title'))
            ->modalDescription(__('filament-translation-toolkit::dashboard.actions.generate_confirm_description', ['table' => $table]))
            ->form([
                Select::make('type')
                    ->label(__('filament-translation-toolkit::dashboard.form.type'))
                    ->options([
                        'resource' => 'Resource',
                        'relation' => 'Relation',
                    ])
                    ->default($type)
                    ->required(),
            ])
            ->action(function (array $data) use ($table): void {
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

                notification()
                    ->title(__('filament-translation-toolkit::dashboard.notifications.generated'))
                    ->success()
                    ->send();
            });
    }

    public function generateAiTableTranslation(string $table, string $type = 'resource'): Action
    {
        return Action::make('generate_ai_'.$table)
            ->label(__('filament-translation-toolkit::dashboard.actions.generate_ai'))
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('filament-translation-toolkit::dashboard.actions.generate_ai_title'))
            ->modalDescription(__('filament-translation-toolkit::dashboard.actions.generate_ai_description', ['table' => $table]))
            ->form([
                Select::make('type')
                    ->label(__('filament-translation-toolkit::dashboard.form.type'))
                    ->options([
                        'resource' => 'Resource',
                        'relation' => 'Relation',
                    ])
                    ->default($type)
                    ->required(),
            ])
            ->action(function (array $data) use ($table): void {
                $exitCode = Artisan::call('make:ai-translation', [
                    'table' => $table,
                    '--type' => $data['type'],
                ]);

                $this->refreshData();

                if ($exitCode === 0) {
                    notification()
                        ->title(__('filament-translation-toolkit::dashboard.notifications.ai_generated'))
                        ->success()
                        ->send();
                } else {
                    notification()
                        ->title(Artisan::output())
                        ->danger()
                        ->send();
                }
            });
    }

    public function generateMissingRelationTranslation(string $model): Action
    {
        return Action::make('generate_relation_'.$model)
            ->label(__('filament-translation-toolkit::dashboard.actions.generate_relation'))
            ->icon('heroicon-o-link')
            ->color('info')
            ->requiresConfirmation()
            ->action(function () use ($model): void {
                $tableName = Str::of(Str::snake(Str::plural($model)))->toString();

                $exitCode = Artisan::call('make:table-translation', [
                    'table' => $tableName,
                ]);

                $this->refreshData();

                if ($exitCode === 0) {
                    notification()
                        ->title(__('filament-translation-toolkit::dashboard.notifications.relation_generated'))
                        ->success()
                        ->send();
                }
            });
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
