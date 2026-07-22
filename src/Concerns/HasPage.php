<?php

namespace Dyahunter35\FilamentTranslationToolkit\Concerns;

use Illuminate\Support\Str;

trait HasPage
{
    use HasTranslateConfigure;

    public static function getLocalePath(): string
    {
        if (property_exists(static::class, 'resource') && isset(static::$resource)) {
            $resource = static::$resource;

            if (property_exists($resource, 'localePath')) {
                return $resource::$localePath;
            }

            return Str::of(class_basename($resource::getModel()))->snake();
        }

        return Str::of(class_basename(static::class))->snake();
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
