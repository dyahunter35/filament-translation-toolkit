<?php

namespace Dyahunter35\FilamentTranslationToolkit\Concerns;

trait Translatable
{
    public static bool $translationEnabled = true;

    /**
     * Override the translation file name (null = auto from model name).
     * Example: 'company' instead of 'company' for Company model.
     */
    public static ?string $translationFileName = null;

    /**
     * Get the translation file name for this model.
     */
    public function getTranslationFileName(): string
    {
        return static::$translationFileName ?? static::getTranslationFileBasename();
    }

    /**
     * Get the base file name (snake_case of model basename).
     */
    public static function getTranslationFileBasename(): string
    {
        return \Illuminate\Support\Str::snake(class_basename(static::class));
    }

    /**
     * Check if translation is enabled for this model.
     */
    public static function isTranslatable(): bool
    {
        return static::$translationEnabled;
    }
}
