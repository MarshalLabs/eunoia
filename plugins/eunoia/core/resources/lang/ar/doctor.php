<?php

return [
    'columns' => [
        'check'   => 'الفحص',
        'status'  => 'الحالة',
        'details' => 'التفاصيل',
    ],
    'status' => [
        'ok'   => 'سليم',
        'warn' => 'تحذير',
        'fail' => 'فشل',
        'skip' => 'تم التخطي',
    ],
    'checks' => [
        'database' => 'الاتصال بقاعدة البيانات',
        'currency' => 'دقة العملة (الدينار الأردني)',
        'queue'    => 'معالج قائمة الانتظار',
        'locale'   => 'اللغة والمنطقة الزمنية',
        'fonts'    => 'الخطوط العربية',
    ],
    'messages' => [
        'database_ok'            => 'متصل (:driver، قاعدة البيانات :database).',
        'currency_missing'       => 'العملة :currency غير موجودة.',
        'currency_wrong'         => 'العملة :currency بعدد خانات عشرية :decimals وتقريب :rounding، والمتوقع 3 و0.001 (طبّق التعديل P1).',
        'currency_zero_rounding' => 'هذه العملات تقريبها صفر أو أقل: :currencies (طبّق التعديل P1).',
        'currency_ok'            => 'الدينار الأردني بثلاث خانات عشرية وتقريب 0.001، ولا توجد عملة تقريبها صفر أو أقل.',
        'queue_ok'               => 'عالج أحد المعالجات مهمة الفحص (:connection).',
        'queue_timeout'          => 'لم يعالج أي معالج مهمة الفحص خلال :seconds ثانية (:connection). شغّل معالجاً: :command',
        'locale_ok'              => 'العربية مع المنطقة الزمنية عمّان.',
        'locale_unexpected'      => 'اللغة :locale والمنطقة الزمنية :timezone، والمتوقع العربية ومنطقة عمّان.',
        'fonts_later'            => 'يُفحص عند إضافة الخطوط المستضافة محلياً في إضافة المظهر (E1-01).',
    ],
];
