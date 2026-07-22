<?php

namespace Dyahunter35\FilamentTranslationToolkit\Templates;

class PageTranslationTemplate extends BaseTranslationTemplate
{
    public function getJsonStructure(): string
    {
        return '
        {
            "ar": {
                "title": "العنوان",
                "heading": "العنوان الرئيسي",
                "subheading": "العنوان الفرعي",
                "navigation": {
                    "label": "تسمية القائمة",
                    "group": "المجموعة"
                },
                "breadcrumbs": {
                    "index": "الرئيسية",
                    "create": "إنشاء",
                    "edit": "تعديل"
                },
                "actions": {
                    "save": "حفظ",
                    "cancel": "إلغاء",
                    "create": "إنشاء",
                    "edit": "تعديل",
                    "delete": "حذف",
                    "view": "عرض"
                },
                "messages": {
                    "saved": "تم الحفظ بنجاح",
                    "deleted": "تم الحذف بنجاح",
                    "not_found": "العنصر غير موجود"
                }
            }
        }';
    }

    public function build(array $langData): string
    {
        $content = "";

        if (!empty($langData['title'])) {
            $content .= "    'title' => '{$this->escape($langData['title'])}',\n";
        }

        if (!empty($langData['heading'])) {
            $content .= "    'heading' => '{$this->escape($langData['heading'])}',\n";
        }

        if (!empty($langData['subheading'])) {
            $content .= "    'subheading' => '{$this->escape($langData['subheading'])}',\n";
        }

        if (!empty($langData['navigation'])) {
            $content .= "    'navigation' => [\n";
            foreach ($langData['navigation'] as $key => $value) {
                $content .= "        '{$this->escape((string) $key)}' => '{$this->escape((string) $value)}',\n";
            }
            $content .= "    ],\n";
        }

        if (!empty($langData['breadcrumbs'])) {
            $content .= "    'breadcrumbs' => [\n";
            foreach ($langData['breadcrumbs'] as $key => $value) {
                $content .= "        '{$this->escape((string) $key)}' => '{$this->escape((string) $value)}',\n";
            }
            $content .= "    ],\n";
        }

        if (!empty($langData['actions'])) {
            $content .= "    'actions' => [\n";
            foreach ($langData['actions'] as $key => $value) {
                $content .= "        '{$this->escape((string) $key)}' => '{$this->escape((string) $value)}',\n";
            }
            $content .= "    ],\n";
        }

        if (!empty($langData['messages'])) {
            $content .= "    'messages' => [\n";
            foreach ($langData['messages'] as $key => $value) {
                $content .= "        '{$this->escape((string) $key)}' => '{$this->escape((string) $value)}',\n";
            }
            $content .= "    ],\n";
        }

        return $content;
    }
}
