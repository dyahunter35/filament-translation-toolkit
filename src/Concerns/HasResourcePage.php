<?php

namespace Dyahunter35\FilamentTranslationToolkit\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait HasResourcePage
{
    use HasTranslateConfigure;

    public static function getLocalePath(): string
    {
        return static::getResource()::getLocalePath();
    }

    public static function getLocale($key): ?string
    {
        $localePath = static::getLocalePath();

        if (Lang::has($key = $localePath.'.'.$key, app()->getLocale())) {
            return __($key);
        }

        return null;
    }

    public static function getNavigationLabel(): string
    {
        return static::getLocale('label.'.Str::snake(class_basename(static::class))) ?? parent::getNavigationLabel();
    }

    public function getTitle(): string|Htmlable
    {
        return static::getLocale('label.'.Str::snake(class_basename(static::class))) ?? parent::getTitle();
    }
}
