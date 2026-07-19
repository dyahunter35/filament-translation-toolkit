<?php

namespace Dyahunter35\FilamentTranslationToolkit\Templates;

abstract class BaseTranslationTemplate
{
    /**
     * Return the JSON structure to send to AI as a prompt skeleton.
     */
    abstract public function getJsonStructure(): string;

    /**
     * Build the final PHP file content from the AI response data.
     */
    abstract public function build(array $langData): string;

    /**
     * Escape strings to avoid PHP syntax errors in translation files.
     */
    protected function escape(?string $string): string
    {
        return str_replace("'", "\'", (string) $string);
    }
}
