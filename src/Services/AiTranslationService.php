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
     *
     * @param  array<string, mixed>  $schemaData
     * @return array<string, mixed>|null
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

        $response = Http::withToken($this->apiKey)
            ->withOptions([
                'proxy' => false,
                'verify' => false,
            ])
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

            // Clean markdown fences to ensure pure JSON extraction
            $content = preg_replace('/```json\s*/i', '', $content);
            $content = preg_replace('/```\s*/i', '', $content);

            return json_decode(trim($content), true);
        }

        throw new Exception('AI API Error: '.$response->body());
    }
}
