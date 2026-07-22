<?php

namespace Dyahunter35\FilamentTranslationToolkit;

use Dyahunter35\FilamentTranslationToolkit\Commands\MakeTranslationCommand;
use Dyahunter35\FilamentTranslationToolkit\Services\TranslationResolver;
use Dyahunter35\FilamentTranslationToolkit\Services\TranslationScanner;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Tables;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentTranslationToolkitServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-translation-toolkit';

    public static string $viewNamespace = 'filament-translation-toolkit';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasCommands([
                MakeTranslationCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->publishConfigFile();
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TranslationScanner::class, function () {
            return new TranslationScanner();
        });
    }

    public function packageBooted(): void
    {
        $this->registerGlobalTranslationCallbacks();
    }

    /**
     * Register global configureUsing callbacks that auto-translate
     * ALL Filament components when a page uses our traits.
     */
    protected function registerGlobalTranslationCallbacks(): void
    {
        if (! config('filament-translation-toolkit.enabled', true)) {
            return;
        }

        // Auto-translate all Component instances (Section, WizardStep, Tab, Repeater)
        Component::configureUsing(function (Component $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            if ($component instanceof \Filament\Schemas\Components\Section) {
                $heading = $component->getHeading();
                if ($heading) {
                    $cleanHeading = (string) str($heading)->snake();
                    if ($localeHeading = TranslationResolver::getTranslation($localePath, 'sections.'.$cleanHeading.'.label')) {
                        $component->heading($localeHeading);
                    }
                    if (method_exists($component, 'getDescription') && $description = TranslationResolver::getTranslation($localePath, 'sections.'.$cleanHeading.'.description')) {
                        $component->description($description);
                    }
                    if (method_exists($component, 'getIcon') && $icon = TranslationResolver::getTranslation($localePath, 'sections.'.$cleanHeading.'.icon')) {
                        $component->icon($icon);
                    }
                }
            }

            if ($component instanceof \Filament\Forms\Components\Repeater) {
                $repeaterLabel = $component->getLabel();
                if ($repeaterLabel) {
                    $cleanRepeater = (string) str($repeaterLabel)->snake();
                    if ($label = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanRepeater.'.label')) {
                        $component->label($label);
                    }
                    if ($desc = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanRepeater.'.description')) {
                        $component->description($desc);
                    }
                }
            }

            if ($component instanceof \Filament\Schemas\Components\Wizard\Step) {
                $label = $component->getLabel();
                if ($label) {
                    $cleanLabel = (string) str($label)->snake();
                    if ($localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanLabel.'.label')) {
                        $component->label($localeLabel);
                    }
                    if (method_exists($component, 'getDescription') && $desc = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanLabel.'.description')) {
                        $component->description($desc);
                    }
                }
            }

            if ($component instanceof \Filament\Schemas\Components\Tabs\Tab) {
                $label = $component->getLabel();
                if ($label) {
                    $cleanLabel = (string) str($label)->snake();
                    if ($localeLabel = TranslationResolver::getTranslation($localePath, 'tabs.'.$cleanLabel.'.label')) {
                        $component->label($localeLabel);
                    }
                    if (method_exists($component, 'getDescription') && $desc = TranslationResolver::getTranslation($localePath, 'tabs.'.$cleanLabel.'.description')) {
                        $component->description($desc);
                    }
                    if (method_exists($component, 'getIcon') && $icon = TranslationResolver::getTranslation($localePath, 'tabs.'.$cleanLabel.'.icon')) {
                        $component->icon($icon);
                    }
                }
            }
        });

        // Auto-translate all Field instances (TextInput, Select, etc.)
        Field::configureUsing(function (Field $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            if (! $component->getLabel()) {
                return;
            }

            $label = (string) str($component->getLabel())
                ->beforeLast('.')
                ->afterLast('.')
                ->kebab()
                ->replace(['-', '_'], ' ')
                ->replaceLast(' id', ' ')
                ->snake();

            if (method_exists($component, 'label') && $localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.label')) {
                $component->label($localeLabel);
            }

            if (method_exists($component, 'description') && $desc = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.description')) {
                $component->description($desc);
            }

            if (method_exists($component, 'placeholder') && $placeholder = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.placeholder')) {
                $component->placeholder($placeholder);
            }

            if (method_exists($component, 'helperText') && $helperText = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.helper_text')) {
                $component->helperText($helperText);
            }

            if (method_exists($component, 'prefix') && $prefix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.prefix')) {
                $component->prefix($prefix);
            }

            if (method_exists($component, 'suffix') && $suffix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.suffix')) {
                $component->suffix($suffix);
            }

            $componentType = get_class($component);
            if (in_array($componentType, [\Filament\Forms\Components\Select::class, \Filament\Forms\Components\Radio::class, \Filament\Forms\Components\Checkbox::class])) {
                $options = $component->getOptions();
                if (is_array($options)) {
                    $translatedOptions = [];
                    foreach ($options as $key => $value) {
                        $optionKey = 'fields.'.$label.'.options.'.Str::slug((string) $key, '_');
                        $translatedOptions[$key] = TranslationResolver::getTranslation($localePath, $optionKey) ?? $value;
                    }
                    $component->options($translatedOptions);
                }
            }
        });

        // Auto-translate all Entry instances (infolist)
        Entry::configureUsing(function (Entry $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            if (! $component->getLabel()) {
                return;
            }

            $label = (string) str($component->getLabel())
                ->beforeLast('.')
                ->afterLast('.')
                ->kebab()
                ->replace(['-', '_'], ' ')
                ->replaceLast(' id', ' ')
                ->snake();

            if (method_exists($component, 'label') && $localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.label')) {
                $component->label($localeLabel);
            }

            if (method_exists($component, 'icon') && $icon = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.icon')) {
                $component->icon($icon);
            }

            if (method_exists($component, 'helperText') && $helperText = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.helper_text')) {
                $component->helperText($helperText);
            }

            if (method_exists($component, 'prefix') && $prefix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.prefix')) {
                $component->prefix($prefix);
            }

            if (method_exists($component, 'suffix') && $suffix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.suffix')) {
                $component->suffix($suffix);
            }

            $componentType = get_class($component);
            if (in_array($componentType, [\Filament\Forms\Components\Select::class, \Filament\Forms\Components\Radio::class, \Filament\Forms\Components\Checkbox::class])) {
                $options = $component->getOptions();
                if (is_array($options)) {
                    $translatedOptions = [];
                    foreach ($options as $key => $value) {
                        $optionKey = 'fields.'.$label.'.options.'.Str::slug((string) $key, '_');
                        $translatedOptions[$key] = TranslationResolver::getTranslation($localePath, $optionKey) ?? $value;
                    }
                    $component->options($translatedOptions);
                }
            }
        });

        // Auto-translate all Table Column instances
        Tables\Columns\Column::configureUsing(function (Tables\Columns\Column $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            $label = (string) Str::of($component->getName())
                ->beforeLast('.')
                ->afterLast('.')
                ->kebab()
                ->replace(['-', '_'], ' ')
                ->replaceLast(' id', ' ')
                ->snake();

            if (method_exists($component, 'label') && $localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.label')) {
                $component->label($localeLabel);
            }

            if (method_exists($component, 'description') && $desc = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.description')) {
                $component->description($desc);
            }

            if (method_exists($component, 'prefix') && $prefix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.prefix')) {
                $component->prefix($prefix);
            }
        });
    }
}
