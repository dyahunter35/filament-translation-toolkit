# Filament Translation Toolkit

A comprehensive, production-ready translation toolkit for **Filament v5** â€” automatic UI translation, AI-powered translation generation, language file scaffolding, and a full dashboard to monitor translation health across your entire application.

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
| **Translatable Models** | Add `Translatable` trait to Eloquent models to opt-in for translation |
| **Automatic UI Translation** | Auto-translate Filament forms, tables, infolists, navigation, and breadcrumbs at runtime |
| **Translation Dashboard** | A full Filament page to monitor API status, missing files, completeness, and relationships |
| **AI-Powered Generation** | Generate smart translation files using OpenRouter API (GPT-4o-mini by default) |
| **Column-Based Generation** | Generate translation scaffolding directly from database table columns |
| **Relationship Detection** | Auto-discovers Eloquent relationships and checks for `_relation` translation files |
| **Completeness Monitoring** | Visual progress bars showing translation completeness per file with missing key tooltips |
| **Per-Class Disable** | Set `$translationEnabled = false` on any class to skip translation |
| **Global Toggle** | `enabled` config or `FILAMENT_TRANSLATION_ENABLED` env to disable everything |
| **Multi-Page Support** | Dedicated traits for Resources, Resource Pages, Single Pages, Relation Managers, and Pages |
| **Multi-Language** | Configurable locale list â€” add any number of languages |
| **Extensible Templates** | Strategy pattern for translation templates â€” create your own template types |

---

## Requirements

- PHP >= 8.2
- Laravel >= 13.x
- Filament >= 5.x
- `spatie/laravel-package-tools` >= 1.15

---

## Installation

### Step 1: Add the Package

```bash
composer require dyahunter35/filament-translation-toolkit
```

### Step 2: Add Views Path to Your Theme (Required for Dashboard Styling)

To ensure Tailwind CSS v4 compiles the classes used in the Translation Dashboard, add a `@source` directive in your main CSS file:

```css
/* resources/css/filament.css */
@import 'tailwindcss';
@source '../../../../vendor/dyahunter35/filament-translation-toolkit/resources/**/*.blade.php';
```

Then rebuild your assets:

```bash
npm run build
```

### Step 3: Publish the Config

```bash
php artisan vendor:publish --tag=filament-translation-toolkit-config
```

This creates `config/filament-translation-toolkit.php` in your project.

### Step 4: Configure Your Locales

Open `config/filament-translation-toolkit.php`:

```php
'locales' => ['en', 'ar'],  // First locale = base language
```

### Step 5: (Optional) Configure AI Translation

If you want AI-powered translation generation:

1. Create a free account at [openrouter.ai](https://openrouter.ai)
2. Generate an API key from [Keys page](https://openrouter.ai/keys)
3. Add to your `.env`:

```env
OPENROUTER_API_KEY=sk-or-v1-xxxxxxxxxxxx
OPENROUTER_MODEL=openai/gpt-4o-mini
```

### Step 6: Register the Dashboard (Optional)

To add the Translation Dashboard to your Filament panel:

```php
// app/Providers/Filament/AdminPanelProvider.php

use Dyahunter35\FilamentTranslationToolkit\Pages\TranslationDashboard;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other config
        ->pages([
            TranslationDashboard::class,
        ]);
}
```

#### Customizing Dashboard Navigation

You can customize the dashboard's navigation label, group, title, icon, and sort order from the config file or via environment variables:

```php
// config/filament-translation-toolkit.php
'navigation' => [
    'label' => 'My Dashboard',           // null = use __() translations
    'group' => 'Admin Tools',            // null = use __() translations
    'title' => 'Translation Overview',   // null = use __() translations
    'icon'  => 'heroicon-o-cog-6-tooth',
    'sort'  => 100,
],
```

Or via `.env`:

```env
TRANSLATION_DASHBOARD_LABEL="My Dashboard"
TRANSLATION_DASHBOARD_GROUP="Admin Tools"
TRANSLATION_DASHBOARD_TITLE="Translation Overview"
TRANSLATION_DASHBOARD_ICON="heroicon-o-cog-6-tooth"
TRANSLATION_DASHBOARD_SORT=100
```

When set to `null` (default), the label, group, and title are automatically translated based on the active locale (English: "Translation Dashboard", Arabic: "لوحة التحكم بالترجمة").

### Step 7: Use Traits in Your Filament Classes

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

    // Use Resource defaults (label, group, icon) from Filament Resource classes
    'use_resource_defaults' => env('TRANSLATION_USE_RESOURCE_DEFAULTS', true),

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

    // Models to exclude from the translation scanner
    'excluded_models' => [
        'Job',
        'Migration',
        'PasswordResetToken',
        'CacheLock',
        'FailedJob',
        'JobBatch',
        'Permission',
        'Role',
    ],

    // Dashboard navigation settings
    // Set any value to null to use translated labels from lang files
    'navigation' => [
        'label' => env('TRANSLATION_DASHBOARD_LABEL', null),
        'group' => env('TRANSLATION_DASHBOARD_GROUP', null),
        'title' => env('TRANSLATION_DASHBOARD_TITLE', null),
        'icon'  => env('TRANSLATION_DASHBOARD_ICON', 'heroicon-o-language'),
        'sort'  => (int) env('TRANSLATION_DASHBOARD_SORT', 999),
    ],

];
```

### Environment Variables

| Variable | Default | Description |
|---|---|---|
| `FILAMENT_TRANSLATION_ENABLED` | `true` | Master switch to enable/disable translation |
| `OPENROUTER_API_KEY` | `''` | OpenRouter API key for AI translations |
| `OPENROUTER_MODEL` | `openai/gpt-4o-mini` | AI model to use for translations |
| `TRANSLATION_DASHBOARD_LABEL` | `null` | Custom label for dashboard nav (null = translated) |
| `TRANSLATION_DASHBOARD_GROUP` | `null` | Custom group name for dashboard nav (null = translated) |
| `TRANSLATION_DASHBOARD_TITLE` | `null` | Custom page title for dashboard (null = translated) |
| `TRANSLATION_DASHBOARD_ICON` | `heroicon-o-language` | Custom icon for dashboard nav |
| `TRANSLATION_DASHBOARD_SORT` | `999` | Sort order for dashboard in sidebar |
| `TRANSLATION_USE_RESOURCE_DEFAULTS` | `true` | Use Resource defaults for navigation values |

---

## Quick Start

### 1. Add the Translatable Trait to Your Models

First, mark your Eloquent models as translatable by adding the `Translatable` trait:

```php
namespace App\Models;

use Dyahunter35\FilamentTranslationToolkit\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Translatable;

    // Optional: customize the translation file name
    // public static ?string $translationFileName = 'company';

    // Optional: disable translation for this model
    // public static bool $translationEnabled = false;
}
```

**Available properties:**

| Property | Type | Default | Description |
|---|---|---|---|
| `$translationEnabled` | `bool` | `true` | Set to `false` to exclude this model from translation |
| `$translationFileName` | `?string` | `null` | Custom file name (null = auto from model name) |

**Available methods:**

```php
$model->getTranslationFileName();  // Get the translation file name
Company::getTranslationFileBasename();  // Get the base file name (snake_case)
Company::isTranslatable();  // Check if translation is enabled
```

### 2. Generate Translation Files for a Table

```bash
# Basic â€” generates scaffolding from column names
php artisan make:table-translation companies

# AI-powered â€” generates smart translations
php artisan make:ai-translation companies --type=resource

# Relation manager
php artisan make:ai-translation documents --type=relation
```

### 3. Add the Filament Traits to Your Resources

That's it â€” **just add the trait**. Translation is auto-activated:

```php
namespace App\Filament\Resources;

use Dyahunter35\FilamentTranslationToolkit\Concerns\HasResource;
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
            // ... your form fields â€” labels auto-translated!
        ]);
    }
}
```

### 4. Add the Trait to Your Relation Manager

```php
namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Dyahunter35\FilamentTranslationToolkit\Concerns\HasRelationManager;
use Filament\Resources\RelationManagers\RelationManager;

class DocumentsRelationManager extends RelationManager
{
    use HasRelationManager;

    protected static string $relationship = 'documents';
}
```

That's it â€” your UI is now automatically translated!

### 5. Disable Translation for a Specific Class

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

### 6. Explicit Boot (Optional)

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
- Scans all database tables with the `Translatable` trait
- Shows which tables are missing translation files per locale
- **Per-language buttons** — e.g., `EN`, `AR` — generates only for that specific locale
- **All** button — generates for all missing locales at once
- **AI buttons per language** — e.g., `AI EN`, `AI AR` — uses AI for that specific locale (only shown if API is configured)
- **Use Resource Defaults toggle** — when enabled, extracts navigation values (label, group, icon) from the Filament Resource class instead of generating generic values
- **Loading indicators**: Each button shows a spinner during processing and becomes disabled to prevent double-clicks. A global banner appears at the top of the dashboard while any action is running.

### 3. Translation Completeness
- Compares the base locale against all other locales
- Shows progress bars with percentages
- Displays key counts (e.g., `45/50`)
- Tooltip on missing keys shows the actual missing key names

### 4. Model Relationships
- Auto-discovers all Eloquent models with the `Translatable` trait
- Shows all public relationship methods per model
- Green badge = translation file exists for that locale, red badge = missing
- **Per-language buttons** — e.g., `EN`, `AR` — generates only for that specific locale
- **All** button — generates for all missing locales at once
- Shows existing locales as green badges when translation already exists

### 5. All Translation Files (Collapsed)
- Lists every translation file
- Shows key count per locale
- Highlights files that are missing in certain locales

---

## Artisan Commands

### `make:table-translation`

Generate a basic translation file from database table columns.

```bash
php artisan make:table-translation {table} {--lang=} {--use-resource-defaults}
```

| Parameter | Description |
|---|---|
| `{table}` | The database table name |
| `--lang` | Specific locale (defaults to all configured locales) |
| `--use-resource-defaults` | Use navigation defaults from Filament Resource class |

**Examples:**
```bash
# Generate for all languages
php artisan make:table-translation companies

# Generate only for English
php artisan make:table-translation companies --lang=en

# Use Resource class defaults (label, group, icon) instead of generic values
php artisan make:table-translation companies --use-resource-defaults

# Combine options
php artisan make:table-translation companies --lang=ar --use-resource-defaults
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
php artisan make:ai-translation {table} {--type=resource} {--lang=} {--use-resource-defaults}
```

| Parameter | Description |
|---|---|
| `{table}` | The database table name |
| `--type` | Template type: `resource` or `relation` |
| `--lang` | Specific locale (defaults to all configured locales) |
| `--use-resource-defaults` | Use navigation defaults from Filament Resource class |

**Examples:**
```bash
# Resource translation for all languages
php artisan make:ai-translation companies --type=resource

# Resource translation only for Arabic
php artisan make:ai-translation companies --type=resource --lang=ar

# Use Resource class defaults (label, group, icon) in the translation
php artisan make:ai-translation companies --type=resource --use-resource-defaults

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

### `Translatable` — Model Trait

Mark your Eloquent models as translatable. The Translation Dashboard only shows models that use this trait.

```php
use Dyahunter35\FilamentTranslationToolkit\Concerns\Translatable;

class Company extends Model
{
    use Translatable;

    // Optional: customize the translation file name
    public static ?string $translationFileName = 'company_info';

    // Optional: disable translation for this specific model
    public static bool $translationEnabled = false;
}
```

**Properties:**

| Property | Type | Default | Description |
|---|---|---|---|
| `$translationEnabled` | `bool` | `true` | Set to `false` to exclude from dashboard |
| `$translationFileName` | `?string` | `null` | Custom file name (null = auto snake_case) |

**Methods:**

```php
$model->getTranslationFileName();      // 'company_info'
Company::getTranslationFileBasename(); // 'company'
Company::isTranslatable();             // true/false
```

---

### `HasTranslateConfigure` — The Core Engine

The central trait that powers all auto-translation. Provides:

```php
// Auto-boot all translation callbacks (optional â€” global auto-registration handles this)
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
| **Global Auto** (recommended) | Service provider registers `configureUsing` globally. Resolver auto-detects current context. | Default â€” just `use HasResource;` |
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

**Locale path** is derived from the model name: `App\Models\Company` â†’ `company`

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

**Locale path** is derived from the class name: `ExpenseSettings` â†’ `expense_settings`

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

namespace Dyahunter35\FilamentTranslationToolkit\Templates;

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

### Auto-Translation Flow (Global Mode â€” Recommended)

```
1. Service provider boots and registers global configureUsing callbacks
   for Field, Column, Entry, and Component classes
2. When any Filament page renders, each component fires the callback
3. TranslationResolver detects the current Livewire component
4. Checks if it uses our traits (HasResource, etc.)
5. Resolves the locale path (e.g., "company" from CompanyResource)
6. Extracts the component label â†’ converts to snake_case key
7. Looks up "fields.{key}.label" in translation files
8. If found â†’ replaces with translated version
9. If not found â†’ keeps original (no breakage)
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
| Resource | `Model` class basename â†’ snake | `CompanyResource` â†’ `company` |
| Single Page | Class basename â†’ snake | `ExpensePage` â†’ `expense_page` |
| Relation Manager | `$relationship` â†’ snake + `_relation` | `documents` â†’ `document_relation` |
| Resource Page | Delegates to parent Resource | `EditCompany` â†’ `company` |
| Page | Delegates to `$resource` model | `CompanyReport` â†’ `company` |

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
cd packages/Dyahunter35/filament-translation-toolkit
composer install
composer test
```

---

## Package Structure

```
packages/Dyahunter35/filament-translation-toolkit/
â”œâ”€â”€ composer.json
â”œâ”€â”€ config/
â”‚   â””â”€â”€ filament-translation-toolkit.php
â”œâ”€â”€ resources/
â”‚   â”œâ”€â”€ lang/
â”‚   â”‚   â”œâ”€â”€ en/dashboard.php
â”‚   â”‚   â””â”€â”€ ar/dashboard.php
â”‚   â””â”€â”€ views/
â”‚       â””â”€â”€ pages/
â”‚           â””â”€â”€ translation-dashboard.blade.php
â””â”€â”€ src/
    â”œâ”€â”€ FilamentTranslationToolkitServiceProvider.php
    â”œâ”€â”€ Commands/
    â”‚   â”œâ”€â”€ MakeTableTranslationCommand.php
    â”‚   â””â”€â”€ MakeTableTranslationAiCommand.php
    â”œâ”€â”€ Concerns/
    â”‚   â”œâ”€â”€ Translatable.php
    â”‚   â”œâ”€â”€ HasTranslateConfigure.php
    â”‚   â”œâ”€â”€ HasResource.php
    â”‚   â”œâ”€â”€ HasSinglePage.php
    â”‚   â”œâ”€â”€ HasResourcePage.php
    â”‚   â”œâ”€â”€ HasPage.php
    â”‚   â””â”€â”€ HasRelationManager.php
    â”œâ”€â”€ Pages/
    â”‚   â””â”€â”€ TranslationDashboard.php
    â”œâ”€â”€ Services/
    â”‚   â”œâ”€â”€ AiTranslationService.php
    â”‚   â””â”€â”€ TranslationScanner.php
    â””â”€â”€ Templates/
        â”œâ”€â”€ BaseTranslationTemplate.php
        â”œâ”€â”€ ResourceTemplate.php
        â””â”€â”€ RelationTemplate.php
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

---

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

---

## Security Vulnerabilities

If you discover a security vulnerability within the package, please send an email to Dyahunter35 via [admin@Dyahunter35.dev](mailto:admin@Dyahunter35.dev). All security vulnerabilities will be promptly addressed.

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
