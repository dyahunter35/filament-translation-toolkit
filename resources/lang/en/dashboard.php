<?php

return [

    'sections' => [
        'api_status' => 'AI Translation Service',
        'missing_tables' => 'Missing Translation Files',
        'completeness' => 'Translation Completeness',
        'relationships' => 'Model Relationships',
        'file_summary' => 'All Translation Files',
    ],

    'api' => [
        'configured' => 'AI translation service is configured and ready.',
        'not_configured' => 'AI translation service is NOT configured.',
        'model' => 'Model',
        'key' => 'Key',
        'steps_intro' => 'To enable AI-powered translation, follow these steps:',
        'step_1' => 'Create a free account at openrouter.ai',
        'step_2' => 'Generate an API key from the Keys page',
        'step_3' => 'Add the key to your .env file',
        'step_4' => 'Optionally, set OPENROUTER_MODEL to change the AI model',
        'get_key' => 'Get your API key at openrouter.ai/keys',
    ],

    'table' => [
        'table' => 'Database Table',
        'suggested_file' => 'Suggested File',
        'exists_in' => 'Exists In',
        'missing_in' => 'Missing In',
        'actions' => 'Actions',
        'model' => 'Model',
        'relationships' => 'Relationships',
        'translation_status' => 'Translation Status',
        'file' => 'Translation File',
        'keys' => ' Keys',
    ],

    'badges' => [
        'all_covered' => 'All Covered',
        'all_translated' => 'All Translated',
        'untranslated' => 'Untranslated',
        'missing' => 'Missing',
    ],

    'messages' => [
        'all_tables_covered' => 'All database tables have translation files in all locales.',
        'no_files_to_compare' => 'No translation files found to compare.',
        'no_relationships' => 'No relationships',
        'not_translated' => 'No translation file',
        'no_models_found' => 'No Eloquent models found.',
        'check_model_namespace' => 'Check that model_namespace in config matches your project structure.',
        'no_translation_files' => 'No translation files found.',
    ],

    'actions' => [
        'refresh' => 'Refresh',
        'generate' => 'Generate',
        'generate_ai' => 'AI Generate',
        'generate_relation' => 'Create Translation',
        'generate_confirm_title' => 'Generate Translation File',
        'generate_confirm_description' => 'This will create a basic translation file for :table. Continue?',
        'generate_ai_title' => 'AI Generate Translation',
        'generate_ai_description' => 'This will use AI to generate a smart translation file for :table. Continue?',
    ],

    'form' => [
        'type' => 'Translation Type',
    ],

    'notifications' => [
        'generated' => 'Translation file generated successfully.',
        'ai_generated' => 'AI translation file generated successfully.',
        'relation_generated' => 'Relation translation file created.',
    ],

];
