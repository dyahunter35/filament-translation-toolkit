<?php

namespace Dyahunter35\FilamentTranslationToolkit\Templates;

class RelationTemplate extends BaseTranslationTemplate
{
    public function getJsonStructure(): string
    {
        return '
        {
            "en": {
                "label": { "plural": "Documents", "single": "Document" },
                "fields": { "column_name": { "label": "...", "placeholder": "..." } },
                "filters": { "created_at": "Filter by Created At" },
                "actions": { "edit": "Edit", "delete": "Delete" }
            },
            "ar": { ... }
        }';
    }

    public function build(array $langData): string
    {
        $content = "    'label' => [\n";
        $content .= "        'plural' => '{$this->escape($langData['label']['plural'] ?? '')}',\n";
        $content .= "        'single' => '{$this->escape($langData['label']['single'] ?? '')}',\n";
        $content .= "    ],\n\n";

        $content .= "    'fields' => [\n";
        foreach ($langData['fields'] ?? [] as $key => $field) {
            $label = is_array($field) ? ($field['label'] ?? $key) : $field;
            $placeholder = is_array($field) ? ($field['placeholder'] ?? '') : '';
            $content .= "        '{$this->escape((string) $key)}' => [\n";
            $content .= "            'label' => '{$this->escape($label)}',\n";
            if (! empty($placeholder)) {
                $content .= "            'placeholder' => '{$this->escape($placeholder)}',\n";
            }
            $content .= "        ],\n";
        }
        $content .= "    ],\n";

        $content .= "    'filters' => [\n";
        foreach ($langData['filters'] ?? [] as $key => $filterLabel) {
            $content .= "        '{$this->escape((string) $key)}' => [\n";
            $content .= "            'label' => '{$this->escape($filterLabel)}',\n";
            $content .= "        ],\n";
        }
        $content .= "    ],\n";

        $content .= "    'actions' => [\n";
        foreach ($langData['actions'] ?? [] as $key => $actionLabel) {
            $content .= "        '{$this->escape((string) $key)}' => '{$this->escape($actionLabel)}',\n";
        }
        $content .= "    ],\n";

        return $content;
    }
}
