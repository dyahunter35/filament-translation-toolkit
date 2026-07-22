<?php

return [

    'navigation' => [
        'label' => 'لوحة الترجمة',
        'group' => 'أدوات',
        'title' => 'لوحة الترجمة',
    ],

    'sections' => [
        'api_status' => 'خدمة ترجمة الذكاء الاصطناعي',
        'missing_tables' => 'ملفات الترجمة المفقودة',
        'completeness' => 'اكتمال الترجمة',
        'relationships' => 'علاقات النماذج',
        'filament_pages' => 'صفحات Filament',
        'file_summary' => 'جميع ملفات الترجمة',
    ],

    'api' => [
        'configured' => 'خدمة ترجمة الذكاء الاصطناعي جاهزة ومُعدّة.',
        'not_configured' => 'خدمة ترجمة الذكاء الاصطناعي غير مُعدّة.',
        'model' => 'النموذج',
        'key' => 'المفتاح',
        'steps_intro' => 'لتفعيل الترجمة بالذكاء الاصطناعي، اتبع الخطوات التالية:',
        'step_1' => 'أنشئ حساباً مجانياً على openrouter.ai',
        'step_2' => 'أنشئ مفتاح API من صفحة المفاتيح',
        'step_3' => 'أضف المفتاح إلى ملف .env',
        'step_4' => 'اختيارياً، عيّن OPENROUTER_MODEL لتغيير نموذج الذكاء الاصطناعي',
        'get_key' => 'احصل على مفتاح API من openrouter.ai/keys',
    ],

    'table' => [
        'table' => 'الجدول',
        'suggested_file' => 'الملف المقترح',
        'exists_in' => 'موجود في',
        'missing_in' => 'مفقود في',
        'actions' => 'الإجراءات',
        'model' => 'النموذج',
        'relationships' => 'العلاقات',
        'translation_status' => 'حالة الترجمة',
        'file' => 'ملف الترجمة',
        'keys' => ' مفاتيح',
        'page' => 'الصفحة',
        'class' => 'الفئة',
        'has_trait' => 'سمة HasPage',
    ],

    'badges' => [
        'all_covered' => 'الكل مُغطّى',
        'all_translated' => 'الكل مُترجم',
        'all_ready' => 'الكل جاهز',
        'untranslated' => 'غير مُترجم',
        'missing' => 'مفقود',
        'no_trait' => 'بدون سمة',
        'no_translation' => 'بدون ترجمة',
    ],

    'messages' => [
        'all_tables_covered' => 'جميع جداول قاعدة البيانات لها ملفات ترجمة في جميع اللغات.',
        'no_files_to_compare' => 'لا توجد ملفات ترجمة للمقارنة.',
        'no_relationships' => 'لا توجد علاقات',
        'not_translated' => 'لا يوجد ملف ترجمة',
        'no_models_found' => 'لم يتم العثور على نماذج قابلة للترجمة.',
        'no_pages_found' => 'لم يتم العثور على صفحات Filament.',
        'create_pages_path' => 'أنشئ الصفحات في app/Filament/Pages لرؤيتها هنا.',
        'add_translatable_trait' => 'أضف سمة Dyahunter35\FilamentTranslationToolkit\Concerns\Translatable إلى نماذج Eloquent لتفعيل الترجمة.',
        'no_translation_files' => 'لا توجد ملفات ترجمة.',
        'processing' => 'جارٍ المعالجة، يرجى الانتظار...',
    ],

    'actions' => [
        'refresh' => 'تحديث',
        'generate' => 'إنشاء',
        'generate_ai' => 'إنشاء بالذكاء الاصطناعي',
        'generate_all' => 'الكل',
        'generate_relation' => 'إنشاء ترجمة',
        'add_trait' => 'إضافة سمة',
        'use_resource_defaults' => 'استخدام قيم المورد الافتراضية',
        'generate_confirm_title' => 'إنشاء ملف ترجمة',
        'generate_confirm_description' => 'سيتم إنشاء ملف ترجمة أساسي للجدول :table. هل تتابع؟',
        'generate_ai_title' => 'إنشاء ترجمة بالذكاء الاصطناعي',
        'generate_ai_description' => 'سيتم استخدام الذكاء الاصطناعي لإنشاء ملف ترجمة ذكي للجدول :table. هل تتابع؟',
    ],

    'form' => [
        'type' => 'نوع الترجمة',
    ],

    'notifications' => [
        'generated' => 'تم إنشاء ملف الترجمة بنجاح.',
        'generated_lang' => 'تم إنشاء ترجمة :lang بنجاح.',
        'ai_generated' => 'تم إنشاء ملف الترجمة بالذكاء الاصطناعي بنجاح.',
        'relation_generated' => 'تم إنشاء ترجمة العلاقات.',
        'relation_generated_lang' => 'تم إنشاء ترجمة العلاقات للغة :lang بنجاح.',
        'page_generated' => 'تم إنشاء ترجمة الصفحة.',
        'page_generated_lang' => 'تم إنشاء ترجمة الصفحة :page للغة :lang بنجاح.',
        'trait_added' => 'تم إضافة سمة HasPage إلى الصفحة :page.',
        'trait_exists' => 'سمة HasPage موجودة بالفعل في هذه الصفحة.',
    ],

];
