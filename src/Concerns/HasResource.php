<?php

namespace Alsultan\FilamentTranslationToolkit\Concerns;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait HasResource
{
    use HasTranslateConfigure;

    public static ?string $localePath = null;

    private static function checkIsSidebarCollapsible(): void
    {
        if (isset(static::$isSidebarCollapsible)) {
            config()->set('filament.layout.sidebar.is_collapsible_on_desktop', static::$isSidebarCollapsible);
        }
    }

    public static function getLocalePath(): string
    {
        return static::$localePath ?? Str::of(class_basename(static::getModel()))->snake();
    }

    public static function getLocale($key): ?string
    {
        $localePath = static::getLocalePath();

        if (Lang::has($key = $localePath.'.'.$key, app()->getLocale())) {
            return __($key);
        }

        return null;
    }

    public static function getBreadcrumb(): string
    {
        static::checkIsSidebarCollapsible();

        return static::getLocale('breadcrumb.index') ?? parent::getBreadcrumb();
    }

    public static function getPluralModelLabel(): string
    {
        return static::getLocale('navigation.plural_label') ?? parent::getPluralModelLabel();
    }

    public static function getModelLabel(): string
    {
        return static::getLocale('navigation.model_label') ?? parent::getModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getLocale('navigation.group') ?? parent::getNavigationGroup();
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        if (static::canView($record) && static::hasPage('view')) {
            return static::getUrl('view', ['record' => $record]);
        }

        if (static::canEdit($record) && static::hasPage('edit')) {
            return static::getUrl('edit', ['record' => $record]);
        }

        if (static::canView($record)) {
            try {
                return static::getUrl('view', ['record' => $record]);
            } catch (\Exception $e) {
                // silently ignore
            }
        }

        return null;
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::getLocale('navigation.icon') ??
            static::$navigationIcon;
    }
}
