<?php

return [
    'tenant_invitation' => [
        'created' => [
            'title' => 'تم إنشاء دعوة مستأجر جديدة',
            'message' => 'تم إنشاء دعوة مستأجر جديدة لـ :ownership. البريد الإلكتروني: :email، الهاتف: :phone، الاسم: :name. دعا بواسطة: :invited_by',
        ],
        'accepted' => [
            'title' => 'تم قبول دعوة المستأجر',
            'message' => ':tenant_name (:tenant_email) قبل الدعوة وسجل كمستأجر لـ :ownership.',
        ],
        'tenant_joined' => [
            'title' => 'انضم مستأجر جديد',
            'message' => ':tenant_name (:tenant_email) انضم إلى :ownership عبر رابط الدعوة. إجمالي المستأجرين من هذه الدعوة: :total_tenants.',
        ],
        'no_email' => 'لا يوجد بريد إلكتروني',
        'no_phone' => 'لا يوجد هاتف',
        'no_name' => 'غير معروف',
        'view_invitation' => 'عرض الدعوة',
        'view_tenant' => 'عرض المستأجر',
    ],
    'contract' => [
        'created' => [
            'title' => 'تم إنشاء عقد جديد',
            'message' => 'تم إنشاء عقد جديد :contract_number للمستأجر :tenant_name (:tenant_email) في :ownership. الحالة: :status. تم الإنشاء بواسطة: :created_by',
        ],
        'status_changed' => [
            'title' => 'تم تغيير حالة العقد',
            'message' => 'تم تغيير حالة العقد :contract_number من :previous_status إلى :new_status للمستأجر :tenant_name (:tenant_email) في :ownership.',
        ],
        'view_contract' => 'عرض العقد',
    ],
    'invoice' => [
        'sent' => [
            'title' => 'فاتورة جديدة :number',
            'message' => 'تم إصدار فاتورة جديدة :number لعقدك. المبلغ: :total ريال. تاريخ الاستحقاق: :due_date. يرجى المراجعة والسداد قبل تاريخ الاستحقاق.',
        ],
    ],
];
