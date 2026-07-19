<?php

namespace Dyahunter35\FilamentTranslationToolkit\Templates;

class ResourceTemplate extends BaseTranslationTemplate
{
    public function getJsonStructure(): string
    {
        return '
        {
            "en": {
                "group": "...", "label": "...", "plural_label": "...", "model_label": "...",
                "breadcrumbs": { "index": "...", "create": "Add ...", "edit": "Edit ..." },
                "fields": { "column1": { "label": "Column 1", "placeholder": "Enter Column 1" } }
            },
            "ar": { ... }
        }';
    }

    public function build(array $langData): string
    {
        $content = "    'navigation' => [\n";
        $content .= "        'group' => '{$this->escape($langData['group'] ?? '')}',\n";
        $content .= "        'label' => '{$this->escape($langData['label'] ?? '')}',\n";
        $content .= "        'plural_label' => '{$this->escape($langData['plural_label'] ?? '')}',\n";
        $content .= "        'model_label' => '{$this->escape($langData['model_label'] ?? '')}',\n";
        $content .= "        'icon' => 'heroicon-m-building-office-2',\n";
        $content .= "    ],\n";
        $content .= "    'breadcrumbs' => [\n";
        $content .= "        'index' => '{$this->escape($langData['breadcrumbs']['index'] ?? '')}',\n";
        $content .= "        'create' => '{$this->escape($langData['breadcrumbs']['create'] ?? '')}',\n";
        $content .= "        'edit' => '{$this->escape($langData['breadcrumbs']['edit'] ?? '')}',\n";
        $content .= "    ],\n";

        $content .= "    'fields' => [\n";
        foreach ($langData['fields'] ?? [] as $key => $field) {
            $label = is_array($field) ? ($field['label'] ?? $key) : $field;
            $placeholder = is_array($field) ? ($field['placeholder'] ?? '') : '';
            $content .= "        '{$this->escape((string) $key)}' => [\n";
            $content .= "            'label' => '{$this->escape($label)}',\n";
            $content .= "            'placeholder' => '{$this->escape($placeholder)}',\n";
            $content .= "        ],\n";
        }
        $content .= "    ],\n";

        return $content;
    }
}
