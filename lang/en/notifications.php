<?php

return [
    'tenant_invitation' => [
        'created' => [
            'title' => 'New Tenant Invitation Created',
            'message' => 'A new tenant invitation has been created for :ownership. Email: :email, Phone: :phone, Name: :name. Invited by: :invited_by',
        ],
        'accepted' => [
            'title' => 'Tenant Invitation Accepted',
            'message' => ':tenant_name (:tenant_email) has accepted the invitation and registered as a tenant for :ownership.',
        ],
        'tenant_joined' => [
            'title' => 'New Tenant Joined',
            'message' => ':tenant_name (:tenant_email) has joined :ownership via invitation link. Total tenants from this invitation: :total_tenants.',
        ],
        'no_email' => 'No email',
        'no_phone' => 'No phone',
        'no_name' => 'Unknown',
        'view_invitation' => 'View Invitation',
        'view_tenant' => 'View Tenant',
    ],
    'contract' => [
        'created' => [
            'title' => 'New Contract Created',
            'message' => 'A new contract :contract_number has been created for tenant :tenant_name (:tenant_email) in :ownership. Status: :status. Created by: :created_by',
        ],
        'status_changed' => [
            'title' => 'Contract Status Changed',
            'message' => 'Contract :contract_number status has been changed from :previous_status to :new_status for tenant :tenant_name (:tenant_email) in :ownership.',
        ],
        'view_contract' => 'View Contract',
    ],
];
