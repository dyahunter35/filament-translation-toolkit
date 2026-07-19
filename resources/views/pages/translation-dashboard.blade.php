<x-filament-panels::page>
    {{-- ============================================ --}}
    {{-- SECTION: API Status                         --}}
    {{-- ============================================ --}}
    <div class="mb-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-server-stack class="h-5 w-5" />
                    {{ __('filament-translation-toolkit::dashboard.sections.api_status') }}
                </div>
            </x-slot>

            @if($aiStatus && $aiStatus['configured'])
                <div class="flex items-center gap-3 rounded-lg bg-success-50 dark:bg-success-950/50 p-4">
                    <div class="flex-shrink-0">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                        </span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-success-700 dark:text-success-300">
                            {{ __('filament-translation-toolkit::dashboard.api.configured') }}
                        </p>
                        <p class="mt-1 text-xs text-success-600 dark:text-success-400">
                            {{ __('filament-translation-toolkit::dashboard.api.model') }}: {{ $aiStatus['model'] }}
                            &middot;
                            {{ __('filament-translation-toolkit::dashboard.api.key') }}: {{ $aiStatus['api_key_preview'] }}
                        </p>
                    </div>
                </div>
            @else
                <div class="rounded-lg bg-danger-50 dark:bg-danger-950/50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-danger-500" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-danger-700 dark:text-danger-300">
                                {{ __('filament-translation-toolkit::dashboard.api.not_configured') }}
                            </p>
                            <div class="mt-2 text-xs text-danger-600 dark:text-danger-400 space-y-2">
                                <p>{{ __('filament-translation-toolkit::dashboard.api.steps_intro') }}</p>
                                <ol class="list-decimal list-inside space-y-1 ml-2">
                                    <li>{{ __('filament-translation-toolkit::dashboard.api.step_1') }}</li>
                                    <li>{{ __('filament-translation-toolkit::dashboard.api.step_2') }}</li>
                                    <li>{{ __('filament-translation-toolkit::dashboard.api.step_3') }}</li>
                                    <li>{{ __('filament-translation-toolkit::dashboard.api.step_4') }}</li>
                                </ol>
                                <div class="mt-3 p-3 rounded bg-danger-100 dark:bg-danger-900/50 font-mono text-xs">
                                    <p class="text-danger-700 dark:text-danger-300"># .env</p>
                                    <p class="text-danger-700 dark:text-danger-300">OPENROUTER_API_KEY=sk-or-v1-xxxxx</p>
                                    <p class="text-danger-700 dark:text-danger-300">OPENROUTER_MODEL=openai/gpt-4o-mini</p>
                                </div>
                                <p class="mt-2">
                                    <a href="https://openrouter.ai/keys" target="_blank" class="underline font-medium">
                                        {{ __('filament-translation-toolkit::dashboard.api.get_key') }}
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- ============================================ --}}
    {{-- SECTION: Missing Table Translations          --}}
    {{-- ============================================ --}}
    <div class="mb-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-magnifying-glass class="h-5 w-5" />
                    {{ __('filament-translation-toolkit::dashboard.sections.missing_tables') }}
                    @if(count($missingTables) > 0)
                        <x-filament::badge color="danger">
                            {{ count($missingTables) }}
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="success">
                            {{ __('filament-translation-toolkit::dashboard.badges.all_covered') }}
                        </x-filament::badge>
                    @endif
                </div>
            </x-slot>

            @if(count($missingTables) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.table') }}</th>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.suggested_file') }}</th>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.exists_in') }}</th>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.missing_in') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('filament-translation-toolkit::dashboard.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($missingTables as $item)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-4 py-3 font-medium">
                                        {{ $item['table'] }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">
                                        {{ $item['suggested_file'] }}.php
                                    </td>
                                    <td class="px-4 py-3">
                                        @foreach($item['exists_in'] as $locale)
                                            <x-filament::badge color="success" size="sm">{{ $locale }}</x-filament::badge>
                                        @endforeach
                                        @if(empty($item['exists_in']))
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @foreach($item['missing_in'] as $locale)
                                            <x-filament::badge color="danger" size="sm">{{ $locale }}</x-filament::badge>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            {{ $this->generateTableTranslation($item['table']) }}
                                            @if($aiStatus && $aiStatus['configured'])
                                                {{ $this->generateAiTableTranslation($item['table']) }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-check-circle class="h-12 w-12 mx-auto mb-3 text-success-500" />
                    <p>{{ __('filament-translation-toolkit::dashboard.messages.all_tables_covered') }}</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- ============================================ --}}
    {{-- SECTION: Translation Completeness            --}}
    {{-- ============================================ --}}
    <div class="mb-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="h-5 w-5" />
                    {{ __('filament-translation-toolkit::dashboard.sections.completeness') }}
                </div>
            </x-slot>

            @if(count($completeness) > 0)
                <div class="space-y-4">
                    @foreach($completeness as $key => $item)
                        @php
                            $parts = explode('/', $key);
                            $fileName = $parts[0];
                            $targetLocale = $parts[1] ?? '?';
                            $percentage = $item['completeness'];
                            $color = $percentage >= 90 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                        @endphp
                        <div class="flex items-center gap-4">
                            <div class="w-48 flex-shrink-0">
                                <span class="font-mono text-xs">{{ $fileName }}.php</span>
                                <span class="text-gray-400 text-xs"> → {{ $targetLocale }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div
                                        class="h-2.5 rounded-full bg-{{ $color }}-500"
                                        style="width: {{ $percentage }}%"
                                    ></div>
                                </div>
                            </div>
                            <div class="w-20 text-right text-xs font-medium">
                                <span class="text-{{ $color }}-600 dark:text-{{ $color }}-400">
                                    {{ $percentage }}%
                                </span>
                            </div>
                            <div class="w-24 text-right text-xs text-gray-500">
                                {{ $item['target_keys'] }}/{{ $item['base_keys'] }}
                            </div>
                            @if(count($item['missing_keys']) > 0)
                                <span title="{{ implode(', ', array_slice($item['missing_keys'], 0, 10)) }}{{ count($item['missing_keys']) > 10 ? '...' : '' }}">
                                    <x-filament::badge color="warning" size="sm">
                                        -{{ count($item['missing_keys']) }}
                                    </x-filament::badge>
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <p>{{ __('filament-translation-toolkit::dashboard.messages.no_files_to_compare') }}</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- ============================================ --}}
    {{-- SECTION: Model Relationships                 --}}
    {{-- ============================================ --}}
    <div class="mb-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-uturn-left class="h-5 w-5" />
                    {{ __('filament-translation-toolkit::dashboard.sections.relationships') }}
                    @php
                        $untranslatedRels = collect($relationships)->where('has_translation', false)->count();
                    @endphp
                    @if($untranslatedRels > 0)
                        <x-filament::badge color="warning">
                            {{ $untranslatedRels }} {{ __('filament-translation-toolkit::dashboard.badges.untranslated') }}
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="success">
                            {{ __('filament-translation-toolkit::dashboard.badges.all_translated') }}
                        </x-filament::badge>
                    @endif
                </div>
            </x-slot>

            @if(count($relationships) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.model') }}</th>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.relationships') }}</th>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.translation_status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('filament-translation-toolkit::dashboard.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($relationships as $item)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-4 py-3 font-medium">
                                        {{ $item['model'] }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(count($item['relationships']) > 0)
                                            @foreach($item['relationships'] as $rel)
                                                <x-filament::badge size="sm">{{ $rel }}</x-filament::badge>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400 text-xs">{{ __('filament-translation-toolkit::dashboard.messages.no_relationships') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item['has_translation'])
                                            <div class="flex items-center gap-2">
                                                <span class="relative flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-success-500"></span>
                                                </span>
                                                <span class="text-xs text-success-600">{{ $item['translation_file'] }}</span>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <span class="relative flex h-2 w-2">
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-danger-500"></span>
                                                </span>
                                                <span class="text-xs text-danger-600">
                                                    {{ __('filament-translation-toolkit::dashboard.messages.not_translated') }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if(!$item['has_translation'])
                                            <div class="flex items-center justify-end gap-1">
                                                {{ $this->generateMissingRelationTranslation($item['model']) }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <p>{{ __('filament-translation-toolkit::dashboard.messages.no_models_found') }}</p>
                    <p class="text-xs mt-1">{{ __('filament-translation-toolkit::dashboard.messages.check_model_namespace') }}</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- ============================================ --}}
    {{-- SECTION: File Summary                        --}}
    {{-- ============================================ --}}
    <div class="mb-6">
        <x-filament::section collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-folder-open class="h-5 w-5" />
                    {{ __('filament-translation-toolkit::dashboard.sections.file_summary') }}
                    <x-filament::badge>{{ count($fileSummary) }}</x-filament::badge>
                </div>
            </x-slot>

            @if(count($fileSummary) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3">{{ __('filament-translation-toolkit::dashboard.table.file') }}</th>
                                @foreach(config('filament-translation-toolkit.locales', ['en', 'ar']) as $locale)
                                    <th class="px-4 py-3 text-center">{{ $locale }} {{ __('filament-translation-toolkit::dashboard.table.keys') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($fileSummary as $item)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-4 py-3 font-mono text-xs">
                                        {{ $item['file'] }}.php
                                    </td>
                                    @foreach(config('filament-translation-toolkit.locales', ['en', 'ar']) as $locale)
                                        <td class="px-4 py-3 text-center">
                                            @if(isset($item['locales'][$locale]))
                                                <span class="font-medium">{{ $item['locales'][$locale] }}</span>
                                            @else
                                                <x-filament::badge color="danger" size="sm">
                                                    {{ __('filament-translation-toolkit::dashboard.badges.missing') }}
                                                </x-filament::badge>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <p>{{ __('filament-translation-toolkit::dashboard.messages.no_translation_files') }}</p>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
