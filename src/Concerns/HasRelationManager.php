<?php

namespace Alsultan\FilamentTranslationToolkit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait HasRelationManager
{
    use HasTranslateConfigure;

    public static function className(): string
    {
        return Str::of(class_basename(static::class))->snake()->value();
    }

    public static function getLocalePath(): string
    {
        if (isset(static::$localePath)) {
            return static::$localePath;
        }

        return Str::of(static::$relationship)->snake()->singular().'_relation';
    }

    public static function getLocale($key): ?string
    {
        $localePath = static::getLocalePath();

        if (! $localePath) {
            return null;
        }

        if (Lang::has($key = $localePath.'.'.$key, app()->getLocale())) {
            return __($key);
        }

        return null;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __(static::$title) ?? (static::getLocale('label.plural') ?? __(Str::title(static::getPluralRecordLabel())));
    }

    protected static function getPluralRecordLabel(): ?string
    {
        return __(static::$pluralModelLabel) ?? (static::getLocale('label.plural') ?? __((string) Str::of(static::getRelationshipName())
            ->kebab()
            ->replace('-', ' ')));
    }

    protected static function getRecordLabel(): ?string
    {
        return __(static::$modelLabel) ?? (static::getLocale('label.single'));
    }
}
