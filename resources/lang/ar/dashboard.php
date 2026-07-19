<?php

return [

    'sections' => [
        'api_status' => 'خدمة الترجمة بالذكاء الاصطناعي',
        'missing_tables' => 'ملفات الترجمة المفقودة',
        'completeness' => 'اكتمال الترجمة',
        'relationships' => 'علاقات النماذج',
        'file_summary' => 'جميع ملفات الترجمة',
    ],

    'api' => [
        'configured' => 'خدمة الترجمة بالذكاء الاصطناعي مُعدة وجاهزة.',
        'not_configured' => 'خدمة الترجمة بالذكاء الاصطناعي غير مُعدّة.',
        'model' => 'الموديل',
        'key' => 'المفتاح',
        'steps_intro' => 'لتفعيل الترجمة بالذكاء الاصطناعي، اتبع الخطوات التالية:',
        'step_1' => 'إنشاء حساب مجاني على openrouter.ai',
        'step_2' => 'إنشاء مفتاح API من صفحة المفاتيح',
        'step_3' => 'إضافة المفتاح إلى ملف .env',
        'step_4' => 'اختيارياً، اضبط OPENROUTER_MODEL لتغيير موديل الذكاء الاصطناعي',
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
        'keys' => ' مفتاح',
    ],

    'badges' => [
        'all_covered' => 'الكل مُغطّى',
        'all_translated' => 'الكل مترجم',
        'untranslated' => 'غير مترجم',
        'missing' => 'مفقود',
    ],

    'messages' => [
        'all_tables_covered' => 'جميع جداول قاعدة البيانات لها ملفات ترجمة بكل اللغات.',
        'no_files_to_compare' => 'لا توجد ملفات ترجمة للمقارنة.',
        'no_relationships' => 'لا توجد علاقات',
        'not_translated' => 'لا يوجد ملف ترجمة',
        'no_models_found' => 'لم يتم العثور على نماذج Eloquent.',
        'check_model_namespace' => 'تأكد من أن model_namespace في الإعدادات يتطابق مع هيكل مشروعك.',
        'no_translation_files' => 'لا توجد ملفات ترجمة.',
        'processing' => 'جاري المعالجة، يرجى الانتظار...',
    ],

    'actions' => [
        'refresh' => 'تحديث',
        'generate' => 'إنشاء',
        'generate_ai' => 'إنشاء بالذكاء الاصطناعي',
        'generate_relation' => 'إنشاء ترجمة',
        'generate_confirm_title' => 'إنشاء ملف ترجمة',
        'generate_confirm_description' => 'سيتم إنشاء ملف ترجمة أساسي لجدول :table. هل تواصل؟',
        'generate_ai_title' => 'إنشاء ترجمة بالذكاء الاصطناعي',
        'generate_ai_description' => 'سيتم استخدام الذكاء الاصطناعي لإنشاء ملف ترجمة ذكي لجدول :table. هل تواصل؟',
    ],

    'form' => [
        'type' => 'نوع الترجمة',
    ],

    'notifications' => [
        'generated' => 'تم إنشاء ملف الترجمة بنجاح.',
        'ai_generated' => 'تم إنشاء ملف الترجمة بالذكاء الاصطناعي بنجاح.',
        'relation_generated' => 'تم إنشاء ملف ترجمة العلاقات.',
    ],

];
