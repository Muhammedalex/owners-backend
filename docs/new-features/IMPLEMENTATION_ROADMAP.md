# Implementation Roadmap - New Features

## Overview

This document outlines the implementation roadmap for the three new features requested by the client.

---

## Feature Priority & Timeline

### Phase 1: Tenant Self-Registration (Weeks 1-2)
**Priority:** High  
**Complexity:** Medium  
**Impact:** High (User Experience)

**Dependencies:**
- Email service configuration
- Token generation system
- Public registration endpoints

**Deliverables:**
- ✅ Tenant invitation system
- ✅ Self-registration flow
- ✅ Email templates
- ✅ Public registration API

---

### Phase 2: Multiple Units per Contract (Weeks 3-4)
**Priority:** Medium  
**Complexity:** Medium  
**Impact:** Medium (Business Logic)

**Dependencies:**
- Contract module (existing)
- Unit module (existing)

**Deliverables:**
- ✅ Contract-Unit pivot table
- ✅ Multi-unit contract creation
- ✅ Invoice generation for multiple units
- ✅ Backward compatibility

---

### Phase 3: Automated Invoices & Reminders (Weeks 5-8)
**Priority:** High  
**Complexity:** High  
**Impact:** High (Automation)

**Dependencies:**
- Invoice module (existing)
- Notification system (existing)
- Email service
- Scheduled jobs system

**Deliverables:**
- ✅ Automated invoice generation
- ✅ Reminder system
- ✅ Multi-channel notification service
- ✅ Payment collector management
- ✅ Notification settings

---

## Implementation Order

### Week 1-2: Tenant Self-Registration

**Day 1-2: Database & Models**
- [ ] Create `tenant_invitations` migration
- [ ] Create `TenantInvitation` model
- [ ] Create `TenantInvitationRepository`
- [ ] Write model tests

**Day 3-4: Services & Business Logic**
- [ ] Create `TenantInvitationService`
- [ ] Implement token generation
- [ ] Implement invitation creation
- [ ] Implement invitation acceptance
- [ ] Write service tests

**Day 5-6: API Endpoints**
- [ ] Create `TenantInvitationController` (owner endpoints)
- [ ] Create `PublicTenantInvitationController` (public endpoints)
- [ ] Create request validation classes
- [ ] Write API tests

**Day 7-8: Email & Notifications**
- [ ] Create email templates (invitation, welcome)
- [ ] Integrate email sending
- [ ] Create notification events
- [ ] Test email delivery

**Day 9-10: Frontend Integration**
- [ ] Owner dashboard: Invite tenant UI
- [ ] Public registration page
- [ ] Token validation
- [ ] Registration form
- [ ] Success/error handling

---

### Week 3-4: Multiple Units per Contract

**Day 1-2: Database & Models**
- [ ] Create `contract_units` migration
- [ ] Create `ContractUnit` pivot model
- [ ] Update `Contract` model (add `units()` relationship)
- [ ] Update `Unit` model (add `contracts()` relationship)
- [ ] Write model tests

**Day 3-4: Services & Business Logic**
- [ ] Update `ContractService` (handle multiple units)
- [ ] Implement unit validation
- [ ] Implement unit attachment logic
- [ ] Update unit status management
- [ ] Write service tests

**Day 5-6: API Updates**
- [ ] Update `StoreContractRequest` (validate `unit_ids`)
- [ ] Update `ContractController` (handle `unit_ids`)
- [ ] Create `AvailableUnitsController` endpoint
- [ ] Update contract resources (include units)
- [ ] Write API tests

**Day 7-8: Invoice Integration**
- [ ] Update `InvoiceService` (generate items for multiple units)
- [ ] Update invoice resources (show units)
- [ ] Test invoice generation

**Day 9-10: Migration & Backward Compatibility**
- [ ] Create migration to populate existing contracts
- [ ] Test backward compatibility
- [ ] Update API documentation
- [ ] Frontend integration

---

### Week 5-8: Automated Invoices & Reminders

**Week 5: Foundation**

**Day 1-2: Database & Models**
- [ ] Create `invoice_reminders` migration
- [ ] Create `payment_collectors` migration
- [ ] Create `InvoiceReminder` model
- [ ] Create `PaymentCollector` model
- [ ] Update `Ownership` model
- [ ] Write model tests

**Day 3-4: Notification Channel System**
- [ ] Create `NotificationChannelInterface`
- [ ] Create `EmailChannel` implementation
- [ ] Create `RealtimeChannel` implementation
- [ ] Create `SMSChannel` skeleton (future)
- [ ] Create `NotificationChannelManager`
- [ ] Write channel tests

**Day 5-6: Automated Invoice Service**
- [ ] Create `AutomatedInvoiceService`
- [ ] Implement billing period calculation
- [ ] Implement invoice generation logic
- [ ] Implement invoice number generation
- [ ] Write service tests

**Week 6: Reminder System**

**Day 1-2: Reminder Service**
- [ ] Create `InvoiceReminderService`
- [ ] Implement reminder scheduling logic
- [ ] Implement reminder type determination
- [ ] Implement reminder tracking
- [ ] Write service tests

**Day 3-4: Notification Service Enhancement**
- [ ] Enhance `NotificationService` with channel support
- [ ] Implement multi-channel sending
- [ ] Implement notification settings reading
- [ ] Write service tests

**Day 5-6: Scheduled Jobs**
- [ ] Create `GenerateInvoices` command
- [ ] Create `SendInvoiceReminders` command
- [ ] Register jobs in scheduler
- [ ] Test scheduled execution

**Week 7: Payment Collectors & Settings**

**Day 1-2: Payment Collector Management**
- [ ] Create `PaymentCollectorService`
- [ ] Create `PaymentCollectorController`
- [ ] Create API endpoints
- [ ] Write API tests

**Day 3-4: Notification Settings**
- [ ] Create notification settings API
- [ ] Add system settings keys
- [ ] Create settings seeder
- [ ] Write tests

**Day 5-6: Email Templates**
- [ ] Create invoice generated templates
- [ ] Create reminder templates
- [ ] Create collector notification templates
- [ ] Test email rendering

**Week 8: Integration & Testing**

**Day 1-2: End-to-End Testing**
- [ ] Test automated invoice generation
- [ ] Test reminder sending
- [ ] Test multi-channel notifications
- [ ] Test payment collector workflow

**Day 3-4: Frontend Integration**
- [ ] Payment collector management UI
- [ ] Notification settings UI
- [ ] Invoice dashboard updates
- [ ] Reminder display

**Day 5-6: Documentation & Deployment**
- [ ] Update API documentation
- [ ] Create user guides
- [ ] Performance testing
- [ ] Deployment preparation

---

## Technical Considerations

### Database Migrations

All migrations should be:
- ✅ Reversible (down() method)
- ✅ Indexed properly
- ✅ Foreign key constrained
- ✅ Tested in staging

### API Versioning

All new endpoints follow V1 structure:
- ✅ Routes in `routes/api/v1/`
- ✅ Controllers in `app/Http/Controllers/Api/V1/`
- ✅ Requests in `app/Http/Requests/V1/`
- ✅ Resources in `app/Http/Resources/V1/`

### Testing Strategy

Each feature requires:
- ✅ Unit tests (Models, Services)
- ✅ Integration tests (API endpoints)
- ✅ Feature tests (End-to-end workflows)
- ✅ Email tests (Mail assertions)

### Security Considerations

- ✅ Token-based invitation system (secure, time-limited)
- ✅ Rate limiting on public endpoints
- ✅ Ownership scoping on all endpoints
- ✅ Permission checks on all actions
- ✅ CSRF protection on public forms

---

## Configuration Requirements

### Environment Variables

```env
# Email Configuration (existing)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Property Management System"

# SMS Configuration (future)
SMS_PROVIDER=twilio  # or custom
SMS_API_KEY=your-api-key
SMS_API_SECRET=your-api-secret
SMS_FROM_NUMBER=+1234567890

# Notification Settings
NOTIFICATION_QUEUE_CONNECTION=database
NOTIFICATION_QUEUE_NAME=notifications
```

### System Settings (Ownership Level)

Settings stored in `system_settings` table:

```php
[
    'invoices.auto_generate' => true,
    'invoices.reminder_days' => [7, 3, 1],
    'invoices.reminder_channels' => ['email', 'realtime'],
    'invoices.sms_enabled' => false,
    'invoices.email_template' => 'default',
    'invoices.overdue_reminder_enabled' => true,
    'invoices.overdue_reminder_frequency' => 7,
]
```

---

## Rollout Strategy

### Phase 1: Tenant Self-Registration
1. Deploy to staging
2. Test with sample invitations
3. Get client approval
4. Deploy to production
5. Monitor for issues

### Phase 2: Multiple Units per Contract
1. Deploy to staging
2. Test with existing contracts (backward compatibility)
3. Test with new multi-unit contracts
4. Get client approval
5. Deploy to production
6. Migrate existing contracts

### Phase 3: Automated Invoices & Reminders
1. Deploy to staging
2. Test invoice generation (dry-run mode)
3. Test reminder system
4. Configure notification settings
5. Get client approval
6. Deploy to production
7. Enable automation gradually

---

## Success Metrics

### Tenant Self-Registration
- ✅ Invitation acceptance rate > 80%
- ✅ Registration completion time < 5 minutes
- ✅ Zero security incidents

### Multiple Units per Contract
- ✅ 100% backward compatibility
- ✅ Zero data loss during migration
- ✅ All existing contracts functional

### Automated Invoices & Reminders
- ✅ Invoice generation accuracy: 100%
- ✅ Reminder delivery rate > 95%
- ✅ Payment collection improvement: +20%

---

## Risk Mitigation

### Technical Risks

1. **Email Delivery Issues**
   - Mitigation: Use queue system, retry mechanism
   - Monitoring: Track email delivery rates

2. **Scheduled Job Failures**
   - Mitigation: Use queue system, error logging
   - Monitoring: Alert on job failures

3. **Database Performance**
   - Mitigation: Proper indexing, query optimization
   - Monitoring: Query performance tracking

### Business Risks

1. **User Adoption**
   - Mitigation: Clear documentation, user training
   - Support: Help desk ready

2. **Data Migration Issues**
   - Mitigation: Thorough testing, rollback plan
   - Support: Data backup before migration

---

## Support & Maintenance

### Documentation
- ✅ API documentation updated
- ✅ User guides created
- ✅ Developer documentation updated

### Monitoring
- ✅ Error logging configured
- ✅ Performance monitoring enabled
- ✅ Email delivery tracking

### Support Plan
- ✅ Help desk training
- ✅ FAQ document
- ✅ Troubleshooting guide

---

## Next Steps

1. **Review & Approval:** Client review of study cases
2. **Kickoff Meeting:** Discuss implementation details
3. **Development Start:** Begin Phase 1 implementation
4. **Regular Updates:** Weekly progress reports
5. **Testing:** Continuous testing throughout development
6. **Deployment:** Staged rollout with monitoring

---

## Questions & Clarifications

Before starting implementation, clarify:

1. **Tenant Self-Registration:**
   - Invitation expiration period? (Default: 7 days)
   - Required fields for registration?
   - Email template customization?

2. **Multiple Units per Contract:**
   - Maximum units per contract?
   - Rent calculation method preference?
   - Unit release workflow?

3. **Automated Invoices:**
   - Invoice generation time? (Default: 2:00 AM)
   - Reminder schedule? (Default: 7, 3, 1 days before)
   - SMS provider preference? (When ready)

---

## Contact

For questions or clarifications:
- **Technical Lead:** [Your Name]
- **Project Manager:** [PM Name]
- **Client Contact:** [Client Name]

---

**Last Updated:** 2025-12-11  
**Version:** 1.0

