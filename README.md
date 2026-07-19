# Filament Translation Toolkit

A comprehensive, production-ready translation toolkit for **Filament v5** — automatic UI translation, AI-powered translation generation, language file scaffolding, and a full dashboard to monitor translation health across your entire application.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Dashboard](#dashboard)
- [Artisan Commands](#artisan-commands)
- [Traits (Concerns)](#traits-concerns)
- [Translation File Structure](#translation-file-structure)
- [AI Translation Service](#ai-translation-service)
- [Templating System](#templating-system)
- [How It Works](#how-it-works)
- [Publishing Assets](#publishing-assets)
- [Testing](#testing)
- [Changelog](#changelog)
- [License](#license)

---

## Features

| Feature | Description |
|---|---|
| **Zero-Boilerplate Translation** | Just `use HasResource;` — global auto-registration handles everything |
| **Automatic UI Translation** | Auto-translate Filament forms, tables, infolists, navigation, and breadcrumbs at runtime |
| **Translation Dashboard** | A full Filament page to monitor API status, missing files, completeness, and relationships |
| **AI-Powered Generation** | Generate smart translation files using OpenRouter API (GPT-4o-mini by default) |
| **Column-Based Generation** | Generate translation scaffolding directly from database table columns |
| **Relationship Detection** | Auto-discovers Eloquent relationships and checks for `_relation` translation files |
| **Completeness Monitoring** | Visual progress bars showing translation completeness per file with missing key tooltips |
| **Per-Class Disable** | Set `$translationEnabled = false` on any class to skip translation |
| **Global Toggle** | `enabled` config or `FILAMENT_TRANSLATION_ENABLED` env to disable everything |
| **Multi-Page Support** | Dedicated traits for Resources, Resource Pages, Single Pages, Relation Managers, and Pages |
| **Multi-Language** | Configurable locale list — add any number of languages |
| **Extensible Templates** | Strategy pattern for translation templates — create your own template types |

---

## Requirements

- PHP >= 8.2
- Laravel >= 13.x
- Filament >= 5.x
- `spatie/laravel-package-tools` >= 1.15

---

## Installation

### Step 1: Add the Package

#### Option A — Local Development (Path Repository)

If the package is in a local `packages/` directory:

```json
// composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/alsultan/filament-translation-toolkit"
        }
    ]
}
```

Then:

```bash
composer require alsultan/filament-translation-toolkit
```

#### Option B — From GitHub

```bash
composer require alsultan/filament-translation-toolkit
```

### Step 2: Publish the Config

```bash
php artisan vendor:publish --tag=filament-translation-toolkit-config
```

This creates `config/filament-translation-toolkit.php` in your project.

### Step 3: Configure Your Locales

Open `config/filament-translation-toolkit.php`:

```php
'locales' => ['en', 'ar'],  // First locale = base language
```

### Step 4: (Optional) Configure AI Translation

If you want AI-powered translation generation:

1. Create a free account at [openrouter.ai](https://openrouter.ai)
2. Generate an API key from [Keys page](https://openrouter.ai/keys)
3. Add to your `.env`:

```env
OPENROUTER_API_KEY=sk-or-v1-xxxxxxxxxxxx
OPENROUTER_MODEL=openai/gpt-4o-mini
```

### Step 5: Register the Dashboard (Optional)

To add the Translation Dashboard to your Filament panel:

```php
// app/Providers/Filament/AdminPanelProvider.php

use Alsultan\FilamentTranslationToolkit\Pages\TranslationDashboard;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other config
        ->pages([
            TranslationDashboard::class,
        ]);
}
```

### Step 6: Use Traits in Your Filament Classes

Start using the translation traits in your existing classes (see [Traits](#traits-concerns) below).

---

## Configuration

The full config file with all options:

```php
// config/filament-translation-toolkit.php

return [

    // Master switch — disable all translation globally
    'enabled' => env('FILAMENT_TRANSLATION_ENABLED', true),

    // Locales to generate translations for (first = base)
    'locales' => ['en', 'ar'],

    // Eloquent models namespace (for relationship detection)
    'model_namespace' => 'App\\Models',

    // Translation files directory (null = base_path('lang'))
    'lang_path' => null,

    // Default Heroicon for generated files
    'default_icon' => 'heroicon-m-building-office-2',

    // AI Translation Service
    'ai' => [
        'api_key'   => env('OPENROUTER_API_KEY', ''),
        'model'     => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
        'endpoint'  => 'https://openrouter.ai/api/v1/chat/completions',
        'timeout'   => 45,
    ],

    // Which keys to include in generated files
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
```

---

## Quick Start

### 1. Generate Translation Files for a Table

```bash
# Basic — generates scaffolding from column names
php artisan make:table-translation companies

# AI-powered — generates smart translations
php artisan make:ai-translation companies --type=resource

# Relation manager
php artisan make:ai-translation documents --type=relation
```

### 2. Add the Trait to Your Resource

That's it — **just add the trait**. Translation is auto-activated:

```php
namespace App\Filament\Resources;

use Alsultan\FilamentTranslationToolkit\Concerns\HasResource;
use Filament\Resources\Resource;

class CompanyResource extends Resource
{
    use HasResource;

    public static function getModel(): string
    {
        return \App\Models\Company::class;
    }

    public static function form(array $form): array
    {
        return $form->schema([
            // ... your form fields — labels auto-translated!
        ]);
    }
}
```

### 3. Add the Trait to Your Relation Manager

```php
namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Alsultan\FilamentTranslationToolkit\Concerns\HasRelationManager;
use Filament\Resources\RelationManagers\RelationManager;

class DocumentsRelationManager extends RelationManager
{
    use HasRelationManager;

    protected static string $relationship = 'documents';
}
```

That's it — your UI is now automatically translated!

### 4. Disable Translation for a Specific Class

If a page doesn't need translation, disable it per-class:

```php
class InternalDashboard extends Resource
{
    use HasResource;

    public static bool $translationEnabled = false; // disables translation

    // ...
}
```

Or disable globally in `.env`:

```env
FILAMENT_TRANSLATION_ENABLED=false
```

Or in config:

```php
'enabled' => false,
```

### 5. Explicit Boot (Optional)

If the global auto-translation doesn't work for your setup, you can still call `bootTranslation()` explicitly:

```php
class CompanyResource extends Resource
{
    use HasResource;

    public static function boot(): void
    {
        static::bootTranslation(); // calls all 3 configure methods
    }
}
```

---

## Dashboard

The `TranslationDashboard` is a comprehensive monitoring page with 5 sections:

### 1. AI Service Status
- **Green indicator**: API key is configured and ready
- **Red indicator with instructions**: Shows step-by-step setup guide including:
  - How to create an OpenRouter account
  - How to generate an API key
  - Where to add it in `.env`
  - Direct link to get the API key

### 2. Missing Translation Files
- Scans all database tables
- Shows which tables are missing translation files per locale
- **Generate** button — creates basic scaffolding from column names
- **AI Generate** button — uses AI to create smart translations (only shown if API is configured)

### 3. Translation Completeness
- Compares the base locale against all other locales
- Shows progress bars with percentages
- Displays key counts (e.g., `45/50`)
- Tooltip on missing keys shows the actual missing key names

### 4. Model Relationships
- Auto-discovers all Eloquent models from your configured namespace
- Shows all public relationship methods per model
- Green dot = translation file exists, red dot = missing
- **Create Translation** button to generate missing relation files

### 5. All Translation Files (Collapsed)
- Lists every translation file
- Shows key count per locale
- Highlights files that are missing in certain locales

---

## Artisan Commands

### `make:table-translation`

Generate a basic translation file from database table columns.

```bash
php artisan make:table-translation {table} {--lang=}
```

| Parameter | Description |
|---|---|
| `{table}` | The database table name |
| `--lang` | Specific locale (defaults to all configured locales) |

**Example:**
```bash
php artisan make:table-translation companies
php artisan make:table-translation companies --lang=en
```

**What it generates:**
```php
// lang/en/company.php
<?php
return [
    'navigation' => [
        'group' => 'Companies',
        'label' => 'Companies',
        'plural_label' => 'Companies',
        'model_label' => 'Company',
        'icon' => 'heroicon-m-building-office-2',
    ],
    'breadcrumbs' => [
        'index' => 'Companies',
        'create' => 'Add Company',
        'edit' => 'Edit Company',
    ],
    'fields' => [
        'id' => [
            'label' => 'Id',
            'placeholder' => '',
        ],
        'name' => [
            'label' => 'Name',
            'placeholder' => '',
        ],
        'created_at' => [
            'label' => 'Created At',
            'placeholder' => '',
        ],
    ],
];
```

---

### `make:ai-translation`

Generate smart translation files using AI.

```bash
php artisan make:ai-translation {table} {--type=resource}
```

| Parameter | Description |
|---|---|
| `{table}` | The database table name |
| `--type` | Template type: `resource` or `relation` |

**Examples:**
```bash
# Resource translation
php artisan make:ai-translation companies --type=resource

# Relation manager translation
php artisan make:ai-translation documents --type=relation
```

**Requirements:**
- `OPENROUTER_API_KEY` must be set in `.env`
- The table must exist in the database

**What it does:**
1. Reads all columns from the database table
2. Detects Eloquent relationships from the model
3. Sends everything to AI with a structured prompt
4. AI returns professional English and Arabic translations
5. Writes `lang/en/{model}.php` and `lang/ar/{model}.php`

---

## Traits (Concerns)

### `HasTranslateConfigure` — The Core Engine

The central trait that powers all auto-translation. Provides:

```php
// Auto-boot all translation callbacks (optional — global auto-registration handles this)
static::bootTranslation();

// Or call individually:
static::translateConfigureForm();     // Auto-translate form fields
static::translateConfigureTable();    // Auto-translate table columns
static::translateConfigureInfolist(); // Auto-translate infolist entries

// Disable translation for this specific class:
public static bool $translationEnabled = false;

// Check if translation is enabled:
if (static::shouldTranslate()) { ... }
```

**How it works (two modes):**

| Mode | How | When to use |
|---|---|---|
| **Global Auto** (recommended) | Service provider registers `configureUsing` globally. Resolver auto-detects current context. | Default — just `use HasResource;` |
| **Explicit Boot** | Call `static::bootTranslation()` in your class's `boot()` method. | If global mode doesn't work for your setup |

**What it translates:**

| Component | Translated Properties |
|---|---|
| `Field` | label, description, placeholder, helper_text, prefix, suffix, options |
| `Section` | heading, description, icon |
| `Repeater` | label, description |
| `Wizard\Step` | label, description |
| `Tab` | label, description, icon |
| `Entry` (infolist) | label, icon, helper_text, prefix, suffix, options |
| `Column` (table) | label, description, prefix |
| `Select/Radio/Checkbox` | options array |

**Additional utilities:**

```php
// Extract all translatable strings from a form
$strings = static::extractTranslatableStrings($form);

// Create a multi-language form component
$component = static::multiLanguageFormComponent($field, ['en', 'ar']);
```

---

### `HasResource`

For Filament Resource classes. Provides:

```php
class CompanyResource extends Resource
{
    use HasResource;

    // Automatically translates:
    // - getNavigationLabel()
    // - getNavigationGroup()
    // - getNavigationIcon()
    // - getModelLabel()
    // - getPluralModelLabel()
    // - getBreadcrumb()
    // - getGlobalSearchResultUrl()
}
```

**Locale path** is derived from the model name: `App\Models\Company` → `company`

You can override it:
```php
public static ?string $localePath = 'custom_company';
```

---

### `HasSinglePage`

For standalone Filament pages (not part of a Resource):

```php
class ExpenseSettings extends Page
{
    use HasSinglePage;

    protected string $view = 'filament.pages.expense-settings';

    // Automatically translates:
    // - getNavigationLabel()
    // - getNavigationGroup()
    // - getActiveNavigationIcon()
    // - getHeading()
    // - getSubheading()
    // - getModelLabel()
    // - getTitle()
}
```

**Locale path** is derived from the class name: `ExpenseSettings` → `expense_settings`

---

### `HasResourcePage`

For Resource sub-pages (View, Edit, Create):

```php
class EditCompany extends EditRecord
{
    use HasResourcePage;

    // Locale path comes from the parent Resource
    // Automatically translates navigation label and title
}
```

---

### `HasRelationManager`

For Relation Manager classes:

```php
class DocumentsRelationManager extends RelationManager
{
    use HasRelationManager;

    protected static string $relationship = 'documents';

    // Locale path: documents_relation
    // Automatically translates:
    // - getTitle()
    // - getPluralRecordLabel()
    // - getRecordLabel()
}
```

---

### `HasPage`

For generic Page classes that belong to a Resource:

```php
class CompanyReport extends Page
{
    use HasPage;

    protected static string $resource = CompanyResource::class;

    // Locale path comes from the parent Resource's model
}
```

---

## Translation File Structure

### Resource Translation File

```php
// lang/en/company.php
<?php
return [
    'navigation' => [
        'group' => 'Companies',
        'label' => 'Companies',
        'plural_label' => 'Companies',
        'model_label' => 'Company',
        'icon' => 'heroicon-m-building-office-2',
    ],
    'breadcrumbs' => [
        'index' => 'Companies',
        'create' => 'Add Company',
        'edit' => 'Edit Company',
    ],
    'sections' => [
        'company_details' => [
            'label' => 'Company Details',
            'description' => 'Basic company information',
            'icon' => 'heroicon-o-building-office',
        ],
    ],
    'fields' => [
        'name' => [
            'label' => 'Company Name',
            'placeholder' => 'Enter company name',
            'helper_text' => 'The legal name of the company',
            'prefix' => '',
            'suffix' => '',
            'description' => '',
            'icon' => '',
        ],
        'status' => [
            'label' => 'Status',
            'options' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
        ],
    ],
    'tabs' => [
        'details' => [
            'label' => 'Details',
            'description' => 'Basic information',
            'icon' => 'heroicon-o-information-circle',
        ],
    ],
    'filters' => [
        'status' => ['label' => 'Filter by Status'],
    ],
    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
];
```

### Relation Translation File

```php
// lang/en/document_relation.php
<?php
return [
    'label' => [
        'plural' => 'Documents',
        'single' => 'Document',
    ],
    'fields' => [
        'title' => [
            'label' => 'Document Title',
            'placeholder' => 'Enter document title',
        ],
        'uploaded_at' => [
            'label' => 'Uploaded At',
        ],
    ],
    'filters' => [
        'created_at' => [
            'label' => 'Filter by Created At',
        ],
    ],
    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
];
```

### Key Naming Convention

| Context | Key Path | Example |
|---|---|---|
| Form field label | `fields.{field_name}.label` | `fields.name.label` |
| Form field placeholder | `fields.{field_name}.placeholder` | `fields.name.placeholder` |
| Section heading | `sections.{section_name}.label` | `sections.details.label` |
| Tab label | `tabs.{tab_name}.label` | `tabs.info.label` |
| Navigation group | `navigation.group` | `navigation.group` |
| Breadcrumb | `breadcrumbs.{page}` | `breadcrumbs.index` |
| Relation label | `label.plural` / `label.single` | `label.plural` |

---

## AI Translation Service

The `AiTranslationService` sends database schema data to OpenRouter API and receives professionally translated UI labels.

### How It Works

1. Reads table columns via `Schema::getColumnListing()`
2. Detects Eloquent relationships via reflection
3. Constructs a prompt with the template structure
4. Sends to OpenRouter API (configurable model)
5. Parses JSON response
6. Writes PHP translation files for each locale

### Supported AI Models

Any model available on OpenRouter:

```env
OPENROUTER_MODEL=openai/gpt-4o-mini          # Default - fast & cheap
OPENROUTER_MODEL=openai/gpt-4o               # Better quality
OPENROUTER_MODEL=anthropic/claude-3-haiku     # Alternative
OPENROUTER_MODEL=google/gemini-flash-1.5     # Alternative
```

### Customizing the Endpoint

If you want to use a different API provider:

```php
// config/filament-translation-toolkit.php
'ai' => [
    'endpoint' => 'https://your-api-endpoint.com/v1/chat/completions',
    'model' => 'your-model-name',
    'api_key' => env('YOUR_API_KEY', ''),
],
```

---

## Templating System

The package uses a Strategy pattern for translation templates. Two templates are included:

### ResourceTemplate

Generates files with `navigation`, `breadcrumbs`, and `fields` keys.

### RelationTemplate

Generates files with `label`, `fields`, `filters`, and `actions` keys.

### Creating a Custom Template

```php
<?php

namespace Alsultan\FilamentTranslationToolkit\Templates;

class WidgetTemplate extends BaseTranslationTemplate
{
    public function getJsonStructure(): string
    {
        return '
        {
            "en": {
                "title": "Widget Title",
                "description": "Widget description",
                "fields": { "stat1": { "label": "Stat 1" } }
            },
            "ar": { ... }
        }';
    }

    public function build(array $langData): string
    {
        $content = "    'title' => '{$this->escape($langData['title'] ?? '')}',\n";
        $content .= "    'description' => '{$this->escape($langData['description'] ?? '')}',\n";

        $content .= "    'fields' => [\n";
        foreach ($langData['fields'] ?? [] as $key => $field) {
            $label = is_array($field) ? ($field['label'] ?? $key) : $field;
            $content .= "        '{$this->escape((string) $key)}' => [\n";
            $content .= "            'label' => '{$this->escape($label)}',\n";
            $content .= "        ],\n";
        }
        $content .= "    ],\n";

        return $content;
    }
}
```

Use it:
```bash
php artisan make:ai-translation widgets --type=widget
```

---

## How It Works

### Auto-Translation Flow (Global Mode — Recommended)

```
1. Service provider boots and registers global configureUsing callbacks
   for Field, Column, Entry, and Component classes
2. When any Filament page renders, each component fires the callback
3. TranslationResolver detects the current Livewire component
4. Checks if it uses our traits (HasResource, etc.)
5. Resolves the locale path (e.g., "company" from CompanyResource)
6. Extracts the component label → converts to snake_case key
7. Looks up "fields.{key}.label" in translation files
8. If found → replaces with translated version
9. If not found → keeps original (no breakage)
10. Per-class $translationEnabled = false skips the class
11. Global 'enabled' config disables everything
```

### Auto-Translation Flow (Explicit Mode)

```
1. You call static::bootTranslation() in your class's boot() method
2. This registers Field::configureUsing(), Column::configureUsing(), etc.
3. Same flow as above from step 3 onwards
```

### Locale Path Resolution

| Class Type | Path Derivation | Example |
|---|---|---|
| Resource | `Model` class basename → snake | `CompanyResource` → `company` |
| Single Page | Class basename → snake | `ExpensePage` → `expense_page` |
| Relation Manager | `$relationship` → snake + `_relation` | `documents` → `document_relation` |
| Resource Page | Delegates to parent Resource | `EditCompany` → `company` |
| Page | Delegates to `$resource` model | `CompanyReport` → `company` |

---

## Publishing Assets

```bash
# Config only
php artisan vendor:publish --tag=filament-translation-toolkit-config

# Views (to customize the dashboard)
php artisan vendor:publish --tag=filament-translation-toolkit-views

# All package assets
php artisan vendor:publish --tag=filament-translation-toolkit
```

Published views will be at `resources/views/vendor/filament-translation-toolkit/`.

---

## Testing

```bash
cd packages/alsultan/filament-translation-toolkit
composer install
composer test
```

---

## Package Structure

```
packages/alsultan/filament-translation-toolkit/
├── composer.json
├── config/
│   └── filament-translation-toolkit.php
├── resources/
│   ├── lang/
│   │   ├── en/dashboard.php
│   │   └── ar/dashboard.php
│   └── views/
│       └── pages/
│           └── translation-dashboard.blade.php
└── src/
    ├── FilamentTranslationToolkitServiceProvider.php
    ├── Commands/
    │   ├── MakeTableTranslationCommand.php
    │   └── MakeTableTranslationAiCommand.php
    ├── Concerns/
    │   ├── HasTranslateConfigure.php
    │   ├── HasResource.php
    │   ├── HasSinglePage.php
    │   ├── HasResourcePage.php
    │   ├── HasPage.php
    │   └── HasRelationManager.php
    ├── Pages/
    │   └── TranslationDashboard.php
    ├── Services/
    │   ├── AiTranslationService.php
    │   └── TranslationScanner.php
    └── Templates/
        ├── BaseTranslationTemplate.php
        ├── ResourceTemplate.php
        └── RelationTemplate.php
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

---

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

---

## Security Vulnerabilities

If you discover a security vulnerability within the package, please send an email to Alsultan via [admin@alsultan.dev](mailto:admin@alsultan.dev). All security vulnerabilities will be promptly addressed.

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
