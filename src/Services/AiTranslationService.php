<?php

namespace Dyahunter35\FilamentTranslationToolkit\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class AiTranslationService
{
    protected string $apiKey;
    protected string $model;
    protected string $endpoint;
    protected int $timeout;

    public function __construct()
    {
        $config = config('filament-translation-toolkit.ai', []);

        $this->apiKey = $config['api_key'] ?? env('OPENROUTER_API_KEY', '');
        $this->model = $config['model'] ?? env('OPENROUTER_MODEL', 'openai/gpt-4o-mini');
        $this->endpoint = $config['endpoint'] ?? 'https://openrouter.ai/api/v1/chat/completions';
        $this->timeout = $config['timeout'] ?? 45;
    }

    /**
     * Send data to AI for translation and formatting.
     */
    public function generateTranslation(array $schemaData, string $templateType, string $jsonStructure): ?array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENROUTER_API_KEY is not set. Publish the config and set your API key, or add it to .env.');
        }

        $prompt = "You are an expert translator for Laravel & Filament PHP frameworks.
        Translate the following database schema columns and model relationships into professional UI labels in English and Arabic.

        The user requested the '{$templateType}' template format.
        Return ONLY a valid, raw JSON object. Do NOT wrap it in markdown blockquotes.
        The JSON must strictly follow this structure:
        {$jsonStructure}

        Note for 'fields': Generate appropriate UI placeholders for inputs.
        Note for 'filters': Generate standard filters based on date/status columns if they exist.
        Note for 'actions': Always include basic actions like 'edit' and 'delete' translations.

        Data to translate: ".json_encode($schemaData);

        $response = Http::withoutVerifying()
            ->withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post($this->endpoint, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You output only raw JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');

            $content = preg_replace('/```json\s*/i', '', $content);
            $content = preg_replace('/```\s*/i', '', $content);

            return json_decode(trim($content), true);
        }

        throw new Exception('AI API Error: '.$response->body());
    }

    /**
     * Translate an existing English translation array to target languages using AI.
     *
     * @param  array<string, mixed>  $englishData  The English translation array
     * @param  string[]  $targetLangs  Languages to translate to (e.g. ['ar'])
     * @return array<string, array<string, mixed>>  Keyed by locale
     */
    public function translateFromEnglish(array $englishData, array $targetLangs, string $tableName): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENROUTER_API_KEY is not set. Publish the config and set your API key, or add it to .env.');
        }

        $langNames = array_map(fn ($l) => match ($l) {
            'ar' => 'Arabic',
            'fr' => 'French',
            'es' => 'Spanish',
            'de' => 'German',
            'tr' => 'Turkish',
            'ur' => 'Urdu',
            default => strtoupper($l),
        }, $targetLangs);

        $langList = implode(' and ', $langNames);
        $langCodes = implode(', ', $targetLangs);

        $prompt = "You are an expert translator for Laravel & Filament PHP frameworks.
        Translate the following English translation file into {$langList}.

        Return ONLY a valid, raw JSON object. Do NOT wrap it in markdown blockquotes.
        The JSON key must be the locale code (e.g. \"{$targetLangs[0]}\").
        Each key in the JSON must match the exact English structure — same keys, same nesting, same array structure.
        Only translate the string VALUES, never change the array keys.
        For placeholders and empty strings, keep them empty.
        For icon values (like 'heroicon-o-xxx'), keep them as-is — do NOT translate icons.

        IMPORTANT: The output structure must be flat at the top level:
        { "group": "...", "label": "...", "plural_label": "...", "model_label": "...", "icon": "...",
          "breadcrumbs": { ... }, "fields": { ... }, "filters": { ... }, "actions": { ... } }

        English source for table [{$tableName}]:
        " . json_encode($englishData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $response = Http::withoutVerifying()
            ->withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post($this->endpoint, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You output only raw JSON. Translate values precisely. Keep all keys and structure identical.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');

            $content = preg_replace('/```json\s*/i', '', $content);
            $content = preg_replace('/```\s*/i', '', $content);

            $decoded = json_decode(trim($content), true);

            if ($decoded && isset($decoded[$targetLangs[0]])) {
                return $decoded;
            }

            return [$targetLangs[0] => $decoded ?? []];
        }

        throw new Exception('AI API Error: '.$response->body());
    }
}
