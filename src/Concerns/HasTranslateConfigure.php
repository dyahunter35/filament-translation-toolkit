<?php

namespace Alsultan\FilamentTranslationToolkit\Concerns;

use Alsultan\FilamentTranslationToolkit\Services\TranslationResolver;
use Filament\Forms;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Wizard\Step;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard\Step as WizardStep;
use Filament\Tables;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait HasTranslateConfigure
{
    protected static array $cachedTranslations = [];

    /**
     * Set to false to disable translation for this specific class.
     */
    public static bool $translationEnabled = true;

    /**
     * Boot translation — call this once in your class's boot() method.
     * Registers all configureUsing callbacks for form, table, and infolist.
     */
    public static function bootTranslation(): void
    {
        if (! static::shouldTranslate()) {
            return;
        }

        static::translateConfigureForm();
        static::translateConfigureTable();
        static::translateConfigureInfolist();
    }

    /**
     * Check if translation is enabled for this class.
     */
    public static function shouldTranslate(): bool
    {
        if (! static::$translationEnabled) {
            return false;
        }

        if (! config('filament-translation-toolkit.enabled', true)) {
            return false;
        }

        return true;
    }

    /**
     * Get a direct translation or generate one automatically from the component properties.
     */
    public static function getSmartTranslation(string $key, array $replacements = []): ?string
    {
        $localePath = static::getLocalePath();
        $fullKey = $localePath.'.'.$key;

        if (Lang::has($fullKey, app()->getLocale())) {
            return __($fullKey, $replacements);
        }

        return null;
    }

    /**
     * Cache translations for performance.
     */
    protected static function cacheTranslations(string $localePath): void
    {
        if (! empty(static::$cachedTranslations[$localePath])) {
            return;
        }

        $translations = trans($localePath);
        static::$cachedTranslations[$localePath] = is_array($translations) ? $translations : [];
    }

    public static function translateConfigureInfolist(): void
    {
        if (! static::shouldTranslate()) {
            return;
        }

        static::cacheTranslations(static::getLocalePath());

        Component::configureUsing(function (Component $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            $componentType = get_class($component);

            if ($component instanceof Section) {
                $heading = $component->getHeading();
                if ($heading) {
                    $cleanHeading = (string) str($heading)->snake();
                    if ($localeHeading = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanHeading.'.label')) {
                        $component->heading($localeHeading);
                    }

                    if (method_exists($component, 'getDescription') && $description = TranslationResolver::getTranslation($localePath, 'form.'.$cleanHeading.'.description')) {
                        $component->description($description);
                    }
                }
            } elseif ($component instanceof WizardStep) {
                $label = $component->getLabel();
                if ($label) {
                    $cleanLabel = (string) str($label)->snake();
                    if ($localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanLabel.'.label')) {
                        $component->label($localeLabel);
                    }

                    if (method_exists($component, 'getDescription') && $description = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanLabel.'.description')) {
                        $component->description($description);
                    }
                }
            }
        });

        Entry::configureUsing(function (Entry $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            $componentType = get_class($component);

            if ($component->getLabel()) {
                $label = (string) str($component->getLabel())
                    ->beforeLast('.')
                    ->afterLast('.')
                    ->kebab()
                    ->replace(['-', '_'], ' ')
                    ->replaceLast(' id', ' ')
                    ->snake();

                if (method_exists($component, 'label')) {
                    if ($localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.label')) {
                        $component->label($localeLabel);
                    }
                }

                if (method_exists($component, 'icon') && $icon = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.icon')) {
                    $component->icon($icon);
                }

                if (method_exists($component, 'helperText') && $localeHelperText = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.helper_text')) {
                    $component->helperText($localeHelperText);
                }

                if (method_exists($component, 'prefix') && $localePrefix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.prefix')) {
                    $component->prefix($localePrefix);
                }

                if (method_exists($component, 'suffix') && $localeSuffix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.suffix')) {
                    $component->suffix($localeSuffix);
                }

                if (in_array($componentType, [Forms\Components\Select::class, Forms\Components\Radio::class, Forms\Components\Checkbox::class])) {
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
            }
        });
    }

    public static function translateConfigureForm(): void
    {
        if (! static::shouldTranslate()) {
            return;
        }

        static::cacheTranslations(static::getLocalePath());

        Component::configureUsing(function (Component $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            $componentType = get_class($component);

            if ($component instanceof Section) {
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

            if ($component instanceof Repeater) {
                $repeaterLabel = $component->getLabel();
                if ($repeaterLabel) {
                    $cleanRepeater = (string) str($repeaterLabel)->snake();
                    if ($localeRepeaterLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanRepeater.'.label')) {
                        $component->label($localeRepeaterLabel);
                    }

                    if ($localeRepeaterDesc = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanRepeater.'.description')) {
                        $component->description($localeRepeaterDesc);
                    }
                }
            } elseif ($component instanceof WizardStep) {
                $label = $component->getLabel();
                if ($label) {
                    $cleanLabel = (string) str($label)->snake();
                    if ($localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanLabel.'.label')) {
                        $component->label($localeLabel);
                    }

                    if (method_exists($component, 'getDescription') && $description = TranslationResolver::getTranslation($localePath, 'fields.'.$cleanLabel.'.description')) {
                        $component->description($description);
                    }
                }
            } elseif ($component instanceof Tab) {
                $label = $component->getLabel();
                if ($label) {
                    $cleanLabel = (string) str($label)->snake();
                    if ($localeLabel = TranslationResolver::getTranslation($localePath, 'tabs.'.$cleanLabel.'.label')) {
                        $component->label($localeLabel);
                    }

                    if (method_exists($component, 'getDescription') && $description = TranslationResolver::getTranslation($localePath, 'tabs.'.$cleanLabel.'.description')) {
                        $component->description($description);
                    }
                    if (method_exists($component, 'getIcon') && $icon = TranslationResolver::getTranslation($localePath, 'tabs.'.$cleanLabel.'.icon')) {
                        $component->icon($icon);
                    }
                }
            }
        });

        Field::configureUsing(function (Field $component): void {
            $localePath = TranslationResolver::resolveLocalePath();
            if (! $localePath) {
                return;
            }

            $componentType = get_class($component);

            if ($component->getLabel()) {
                $label = (string) str($component->getLabel())
                    ->beforeLast('.')
                    ->afterLast('.')
                    ->kebab()
                    ->replace(['-', '_'], ' ')
                    ->replaceLast(' id', ' ')
                    ->snake();

                if (method_exists($component, 'label')) {
                    if ($localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.label')) {
                        $component->label($localeLabel);
                    }
                }

                if (method_exists($component, 'description') && $localeDescription = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.description')) {
                    $component->description($localeDescription);
                }

                if (method_exists($component, 'placeholder') && $localePlaceholder = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.placeholder')) {
                    $component->placeholder($localePlaceholder);
                }

                if (method_exists($component, 'helperText') && $localeHelperText = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.helper_text')) {
                    $component->helperText($localeHelperText);
                }

                if (method_exists($component, 'prefix') && $localePrefix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.prefix')) {
                    $component->prefix($localePrefix);
                }

                if (method_exists($component, 'suffix') && $localeSuffix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.suffix')) {
                    $component->suffix($localeSuffix);
                }

                if (in_array($componentType, [Forms\Components\Select::class, Forms\Components\Radio::class, Forms\Components\Checkbox::class])) {
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
            }
        });
    }

    public static function translateConfigureTable(): void
    {
        if (! static::shouldTranslate()) {
            return;
        }

        Tables\Columns\Column::configureUsing(function (Tables\Columns\Column $component) {
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

            if (method_exists($component, 'label')) {
                if ($localeLabel = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.label')) {
                    $component->label($localeLabel);
                }
            }

            if (method_exists($component, 'description') && $localeDescription = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.description')) {
                $component->description($localeDescription);
            }

            if (method_exists($component, 'prefix') && $localePrefix = TranslationResolver::getTranslation($localePath, 'fields.'.$label.'.prefix')) {
                $component->prefix($localePrefix);
            }
        });
    }

    public static function multiLanguageFormComponent(Forms\Components\Component $formComponent, array $languages = ['ar', 'en']): array
    {
        $name = $formComponent->getName();

        return [
            Forms\Components\Tabs::make($name.'_tab')
                ->tabs(
                    collect($languages)->map(fn ($language) => Forms\Components\Tabs\Tab::make($language.'tabs')
                        ->label(__('language.'.$language))
                        ->schema([
                            (clone $formComponent)->name($name.'.'.$language)
                                ->statePath($name.'.'.$language),
                        ]))->toArray()
                ),
        ];
    }

    /**
     * Extract all translatable strings from a form.
     */
    public static function extractTranslatableStrings(Forms\Form $form): array
    {
        $strings = [];

        $extractFromComponent = function ($component) use (&$strings, &$extractFromComponent) {
            if (method_exists($component, 'getLabel')) {
                $label = $component->getLabel();
                if ($label) {
                    $cleanLabel = (string) str($label)->snake();

                    $strings['fields'][$cleanLabel]['label'] = $label;

                    if (method_exists($component, 'getHelperText')) {
                        $helperText = $component->getHelperText();
                        if ($helperText) {
                            $strings['fields'][$cleanLabel]['helper_text'] = $helperText;
                        }
                    }

                    if (method_exists($component, 'getDescription')) {
                        $description = $component->getDescription();
                        if ($description) {
                            $strings['fields'][$cleanLabel]['description'] = $description;
                        }
                    }
                }
            }

            if ($component instanceof Section && method_exists($component, 'getHeading')) {
                $heading = $component->getHeading();
                if ($heading) {
                    $cleanHeading = (string) str($heading)->snake();
                    $strings['fields'][$cleanHeading]['label'] = $heading;

                    if (method_exists($component, 'getDescription')) {
                        $description = $component->getDescription();
                        if ($description) {
                            $strings['fields'][$cleanHeading]['description'] = $description;
                        }
                    }
                }
            }

            if ($component instanceof Step && method_exists($component, 'getLabel')) {
                $stepLabel = $component->getLabel();
                if ($stepLabel) {
                    $cleanLabel = (string) str($stepLabel)->snake();
                    $strings['fields'][$cleanLabel]['label'] = $stepLabel;

                    if (method_exists($component, 'getDescription')) {
                        $description = $component->getDescription();
                        if ($description) {
                            $strings['fields'][$cleanLabel]['description'] = $description;
                        }
                    }
                }
            }

            if (method_exists($component, 'getChildComponents')) {
                $children = $component->getChildComponents();
                foreach ($children as $child) {
                    $extractFromComponent($child);
                }
            }

            return $strings;
        };

        foreach ($form->getComponents() as $component) {
            $extractFromComponent($component);
        }

        return $strings;
    }
}
