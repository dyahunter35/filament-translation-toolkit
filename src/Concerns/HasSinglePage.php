<?php

namespace Dyahunter35\FilamentTranslationToolkit\Concerns;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

trait HasSinglePage
{
    use HasTranslateConfigure;

    public function getModelLabel(): ?string
    {
        $localePath = static::getLocalePath();

        if (trans()->has($locale = $localePath.'.'.'navigation.heading', [], app()->getLocale())) {
            return trans($locale);
        }

        return $this->heading ?? $this->getHeading();
    }

    public static function className(): string
    {
        return Str::of(class_basename(static::class))->snake()->value();
    }

    public static function getLocalePath(): string
    {
        return static::className();
    }

    public static function getLocale($key): ?string
    {
        $localePath = static::getLocalePath();

        if (trans()->has($locale = $localePath.'.'.$key, [], app()->getLocale())) {
            return trans($locale);
        }

        return null;
    }

    public function getHeading(): string|Htmlable
    {
        $localePath = static::getLocalePath();

        if (trans()->has($locale = $localePath.'.'.'navigation.heading', [], app()->getLocale())) {
            return trans($locale);
        }

        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        $localePath = static::getLocalePath();

        if (trans()->has($locale = $localePath.'.'.'navigation.sub_heading.', [], app()->getLocale())) {
            return trans($locale);
        }

        return null;
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getLocale('navigation.group') ?? parent::getNavigationGroup();
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        $localePath = static::getLocalePath();

        if (trans()->has($locale = $localePath.'.'.'navigation.icon', [], app()->getLocale())) {
            return trans($locale);
        }

        return static::$activeNavigationIcon ?? static::getNavigationIcon();
    }

    public static function getNavigationLabel(): string
    {
        $localePath = static::getLocalePath();

        if (trans()->has($locale = $localePath.'.'.'navigation.heading', [], app()->getLocale())) {
            return trans($locale);
        }

        return static::$navigationLabel ?? static::$title ?? str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }
}
