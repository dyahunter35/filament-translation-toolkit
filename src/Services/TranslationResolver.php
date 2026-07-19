<?php

namespace Alsultan\FilamentTranslationToolkit\Services;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Livewire\LivewireManager;

class TranslationResolver
{
    protected static ?string $cachedLocalePath = null;

    /**
     * Resolve the locale path from the current Livewire context.
     */
    public static function resolveLocalePath(): ?string
    {
        if (static::$cachedLocalePath !== null && ! app()->isProduction()) {
            return static::$cachedLocalePath;
        }

        try {
            $component = app(LivewireManager::class)->current();
        } catch (\Exception $e) {
            return null;
        }

        if (! $component) {
            return null;
        }

        // Check if the current Livewire component has getLocalePath()
        if (method_exists($component, 'getLocalePath')) {
            $localePath = $component::getLocalePath();

            // Check if translation is disabled for this component
            if (property_exists($component, 'translationEnabled') && ! $component::$translationEnabled) {
                return null;
            }

            if ($localePath) {
                static::$cachedLocalePath = $localePath;

                return $localePath;
            }
        }

        // Try to resolve from resource if it's a resource page/relation manager
        if (method_exists($component, 'getResource')) {
            $resource = $component::getResource();

            if (method_exists($resource, 'getLocalePath')) {
                static::$cachedLocalePath = $resource::getLocalePath();

                return static::$cachedLocalePath;
            }
        }

        return null;
    }

    /**
     * Clear the cached locale path (call between requests).
     */
    public static function clearCache(): void
    {
        static::$cachedLocalePath = null;
    }

    /**
     * Check if a translation key exists for the given path.
     */
    public static function hasTranslation(string $localePath, string $key): bool
    {
        return Lang::has($localePath.'.'.$key, app()->getLocale());
    }

    /**
     * Get a translation for the given path and key.
     */
    public static function getTranslation(string $localePath, string $key, array $replacements = []): ?string
    {
        $fullKey = $localePath.'.'.$key;

        if (Lang::has($fullKey, app()->getLocale())) {
            return __($fullKey, $replacements);
        }

        return null;
    }
}
