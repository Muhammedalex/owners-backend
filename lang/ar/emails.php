<?php

return [
    'tenant_invitation' => [
        'subject' => 'تمت دعوتك للتسجيل كمستأجر - :ownership',
        'greeting' => 'عزيزي/عزيزتي :name',
        'future_tenant' => 'المستأجر المستقبلي',
        'intro' => 'تمت دعوتك من قبل :ownership للتسجيل كمستأجر في نظام إدارة الممتلكات الخاص بهم.',
        'ownership' => 'الملكية',
        'invited_email' => 'البريد الإلكتروني المدعو',
        'invited_phone' => 'رقم الهاتف المدعو',
        'instructions' => 'انقر على الزر أدناه لإكمال تسجيلك وإنشاء ملف المستأجر الخاص بك.',
        'register_button' => 'إكمال التسجيل',
        'expiry_warning' => 'سينتهي صلاحية هذا الرابط في :date.',
        'notes' => 'ملاحظات',
        'ignore_message' => 'إذا لم تتوقع هذه الدعوة، يرجى تجاهل هذا البريد الإلكتروني.',
        'footer' => 'مع أطيب التحيات،<br>:ownership',
        'copyright' => '© :year نظام إدارة الممتلكات. جميع الحقوق محفوظة.',
    ],
    'contract' => [
        'created' => [
            'subject' => 'تم إنشاء عقد جديد :contract_number - :ownership',
            'greeting' => 'عزيزي/عزيزتي :name',
            'intro' => 'تم إنشاء عقد جديد لك.',
            'contract_number' => 'رقم العقد',
            'status' => 'الحالة',
            'tenant' => 'المستأجر',
            'ownership' => 'الملكية',
            'view_contract' => 'عرض العقد',
            'footer' => 'مع أطيب التحيات،<br>:ownership',
            'copyright' => '© :year نظام إدارة الممتلكات. جميع الحقوق محفوظة.',
        ],
        'status_changed' => [
            'subject' => 'تم تغيير حالة العقد :contract_number إلى :new_status - :ownership',
            'greeting' => 'عزيزي/عزيزتي :name',
            'intro' => 'تم تغيير حالة عقدك.',
            'contract_number' => 'رقم العقد',
            'previous_status' => 'الحالة السابقة',
            'new_status' => 'الحالة الجديدة',
            'tenant' => 'المستأجر',
            'ownership' => 'الملكية',
            'view_contract' => 'عرض العقد',
            'footer' => 'مع أطيب التحيات،<br>:ownership',
            'copyright' => '© :year نظام إدارة الممتلكات. جميع الحقوق محفوظة.',
        ],
    ],
];

