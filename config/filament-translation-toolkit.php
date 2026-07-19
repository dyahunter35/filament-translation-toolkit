<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Translation
    |--------------------------------------------------------------------------
    |
    | Master switch for the entire translation system. Set to false to
    | disable all auto-translation globally. You can also disable per-class
    | by setting public static bool $translationEnabled = false; in any
    | class that uses HasTranslateConfigure.
    |
    */

    'enabled' => env('FILAMENT_TRANSLATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The locales that will be used when generating translation files.
    | The first locale is considered the base/target language.
    |
    */

    'locales' => ['en', 'ar'],

    /*
    |--------------------------------------------------------------------------
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | The base namespace for your Eloquent models. This is used by the
    | AI translation command to extract model relationships via reflection.
    |
    */

    'model_namespace' => 'App\\Models',

    /*
    |--------------------------------------------------------------------------
    | Translation File Path
    |--------------------------------------------------------------------------
    |
    | The base path where translation files are stored. By default, this
    | points to the Laravel `lang` directory.
    |
    */

    'lang_path' => null, // null = base_path('lang')

    /*
    |--------------------------------------------------------------------------
    | Default Icon
    |--------------------------------------------------------------------------
    |
    | The default Heroicon used when generating new translation files.
    |
    */

    'default_icon' => 'heroicon-m-building-office-2',

    /*
    |--------------------------------------------------------------------------
    | AI Translation Service
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI-powered translation command. The service
    | uses OpenRouter API by default. You can swap the provider or model.
    |
    | Requires: illuminate/http (usually included with Laravel)
    |
    */

    'ai' => [
        /*
         * OpenRouter API key. Falls back to env('OPENROUTER_API_KEY').
         */
        'api_key' => env('OPENROUTER_API_KEY', ''),

        /*
         * The AI model to use for translations.
         */
        'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),

        /*
         * API endpoint.
         */
        'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',

        /*
         * Request timeout in seconds.
         */
        'timeout' => 45,
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Structure Keys
    |--------------------------------------------------------------------------
    |
    | Define which keys to include when generating translation files.
    | This controls the structure of the generated PHP translation arrays.
    |
    */

    'structure' => [
        'resource' => [
            'navigation' => true,
            'breadcrumbs' => true,
            'fields' => true,
            'sections' => true,
            'filters' => true,
            'actions' => true,
            'widgets' => false,
        ],
        'relation' => [
            'label' => true,
            'fields' => true,
            'filters' => true,
            'actions' => true,
        ],
    ],

];
