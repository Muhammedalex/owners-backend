<?php

return [
    'tenant_invitation' => [
        'subject' => 'You\'re invited to register as a tenant - :ownership',
        'greeting' => 'Dear :name',
        'future_tenant' => 'Future Tenant',
        'intro' => 'You have been invited by :ownership to register as a tenant in their property management system.',
        'ownership' => 'Ownership',
        'invited_email' => 'Invited Email',
        'invited_phone' => 'Invited Phone',
        'instructions' => 'Click the button below to complete your registration and create your tenant profile.',
        'register_button' => 'Complete Registration',
        'expiry_warning' => 'This link will expire on :date.',
        'notes' => 'Notes',
        'ignore_message' => 'If you did not expect this invitation, please ignore this email.',
        'footer' => 'Best regards,<br>:ownership',
        'copyright' => '© :year Property Management System. All rights reserved.',
    ],
    'contract' => [
        'created' => [
            'subject' => 'New Contract :contract_number Created - :ownership',
            'greeting' => 'Dear :name',
            'intro' => 'A new contract has been created for you.',
            'contract_number' => 'Contract Number',
            'status' => 'Status',
            'tenant' => 'Tenant',
            'ownership' => 'Ownership',
            'view_contract' => 'View Contract',
            'footer' => 'Best regards,<br>:ownership',
            'copyright' => '© :year Property Management System. All rights reserved.',
        ],
        'status_changed' => [
            'subject' => 'Contract :contract_number status changed to :new_status - :ownership',
            'greeting' => 'Dear :name',
            'intro' => 'The status of your contract has been changed.',
            'contract_number' => 'Contract Number',
            'previous_status' => 'Previous Status',
            'new_status' => 'New Status',
            'tenant' => 'Tenant',
            'ownership' => 'Ownership',
            'view_contract' => 'View Contract',
            'footer' => 'Best regards,<br>:ownership',
            'copyright' => '© :year Property Management System. All rights reserved.',
        ],
    ],
];

