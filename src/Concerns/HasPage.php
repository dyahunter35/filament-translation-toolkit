<?php

namespace Dyahunter35\FilamentTranslationToolkit\Concerns;

use Illuminate\Support\Str;

trait HasPage
{
    use HasTranslateConfigure;

    public static function getLocalePath(): string
    {
        if (isset(static::$resource::$localePath)) {
            return static::$localePath;
        }

        return 'locale/'.Str::of(class_basename(static::$resource::getModel()))->snake();
    }

    public static function getLocale($key): ?string
    {
        $localePath = static::getLocalePath();

        if (trans()->has($locale = $localePath.'.'.$key, [], app()->getLocale())) {
            return trans($locale);
        }

        return null;
    }
}
