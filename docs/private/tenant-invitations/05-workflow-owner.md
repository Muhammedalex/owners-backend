# Workflow - Owner Side

## Overview

This document describes the workflow for owners to create and manage tenant invitations.

---

## Creating Invitations

### Scenario 1: Single Invitation with Email

**Use Case:** Inviting a specific person via email

#### Steps

1. **Navigate to Tenants Page**
   - Owner goes to Dashboard → Tenants
   - Clicks "Invite Tenant" button

2. **Fill Invitation Form**
   ```
   Email: tenant@example.com
   Name: Ahmed Ali (optional)
   Expiration: 7 days (default)
   Notes: New office tenant (optional)
   ```

3. **Submit Form**
   - System validates data
   - Creates invitation record
   - Generates secure token
   - Sends email with invitation link

4. **Confirmation**
   - Owner sees success message
   - Invitation appears in invitations list
   - Status: `pending`

#### Result

- Email sent to `tenant@example.com`
- Invitation link: `https://app.example.com/register/tenant?token=abc123...`
- Invitation expires in 7 days
- Can only be accepted once

---

### Scenario 2: Single Invitation with Phone

**Use Case:** Inviting via SMS (when SMS service is ready)

#### Steps

1. **Fill Invitation Form**
   ```
   Phone: +966501234567
   Name: Ahmed Ali (optional)
   Expiration: 7 days
   ```

2. **Submit Form**
   - System creates invitation
   - SMS sent (future feature)
   - Link generated for manual sharing

#### Result

- Invitation created
- SMS sent (when implemented)
- Link available for sharing

---

### Scenario 3: Multi-use Invitation (No Email/Phone)

**Use Case:** Public invitation link for website/social media

#### Steps

1. **Fill Invitation Form**
   ```
   Email: (leave empty)
   Phone: (leave empty)
   Name: (optional)
   Expiration: 30 days (longer for public links)
   Notes: Public invitation for website
   ```

2. **Submit Form**
   - System creates invitation
   - **No email sent**
   - Link generated

3. **Share Link**
   - Owner copies invitation link
   - Shares on website, social media, etc.

#### Result

- Invitation link generated
- Multiple tenants can use same link
- Status remains `pending` until manually closed
- Owner can see all tenants who joined

---

### Scenario 4: Bulk Invitations

**Use Case:** Inviting multiple tenants at once

#### Steps

1. **Navigate to Bulk Invite**
   - Owner goes to Tenants → Bulk Invite

2. **Upload/Enter Multiple Emails**
   ```
   Invitations:
   - tenant1@example.com, Name: One
   - tenant2@example.com, Name: Two
   - tenant3@example.com, Name: Three
   
   Expiration: 7 days
   Notes: Bulk invitation for new building
   ```

3. **Submit**
   - System creates all invitations
   - Sends emails to all recipients

#### Result

- Multiple invitations created
- Emails sent to all recipients
- All invitations tracked separately

---

## Managing Invitations

### Viewing Invitations List

**Location:** Tenants → Invitations

**Filters Available:**
- Status: `pending`, `accepted`, `expired`, `cancelled`
- Search: Email, name, or token
- Date range

**Displayed Information:**
- Email/Phone
- Name
- Status
- Expiration date
- Created date
- Invited by
- Accepted by (if accepted)
- Tenant details (if accepted)

---

### Resending Invitation

**Use Case:** Tenant didn't receive email or needs new link

#### Steps

1. **Find Invitation**
   - Go to Invitations list
   - Find the invitation

2. **Click "Resend"**
   - System validates invitation has email
   - Checks invitation is still valid
   - Resends email with same token

#### Result

- Email resent to same address
- Same token/link (still valid)
- Updated timestamp

**Note:** Only works for invitations with email addresses.

---

### Cancelling Invitation

**Use Case:** Owner wants to revoke an invitation

#### Steps

1. **Find Invitation**
   - Go to Invitations list
   - Find pending invitation

2. **Click "Cancel"**
   - System validates permissions
   - Marks invitation as `cancelled`

#### Result

- Invitation status: `cancelled`
- Token no longer valid
- Cannot be accepted

**Special Case - Multi-use Invitations:**
- Requires `tenants.invitations.close_without_contact` permission
- Closes invitation manually
- Prevents new tenants from joining

---

### Viewing Invitation Details

**Use Case:** Owner wants to see full invitation details

#### Information Displayed

**Single-use Invitation:**
- Basic info (email, name, status)
- Invitation link
- Expiration date
- Created/accepted dates
- Tenant who accepted
- User who accepted

**Multi-use Invitation:**
- Basic info (no email/phone)
- Invitation link
- Expiration date
- **Tenants count** (how many joined)
- **List of all tenants** who joined
- Created date

---

## Best Practices

### 1. Expiration Settings

- **Single-use (Email):** 7 days (default)
- **Multi-use (Public):** 30 days (longer for public links)
- **Bulk invitations:** 7 days (standard)

### 2. Email Invitations

- Always include name if known
- Add notes for context
- Use appropriate expiration based on urgency

### 3. Multi-use Invitations

- Use longer expiration (30 days)
- Monitor tenant count regularly
- Close when no longer needed
- Track where link was shared

### 4. Bulk Invitations

- Verify email addresses before sending
- Use consistent expiration
- Add notes for tracking

---

## Common Workflows

### Workflow 1: New Building Launch

```
1. Create multi-use invitation (30 days)
2. Share link on website
3. Monitor tenant registrations
4. Close invitation when building full
```

### Workflow 2: Specific Tenant Onboarding

```
1. Create single invitation with email
2. Email sent automatically
3. Wait for tenant to register
4. Follow up if not registered within 3 days
5. Resend if needed
```

### Workflow 3: Bulk Tenant Migration

```
1. Prepare list of tenant emails
2. Create bulk invitations
3. All emails sent automatically
4. Track acceptance rates
5. Follow up with non-responders
```

---

## Troubleshooting

### Issue: Email Not Received

**Solutions:**
1. Check spam folder
2. Verify email address is correct
3. Use "Resend" button
4. Check email logs (`storage/logs/emails.log`)

### Issue: Invitation Expired

**Solutions:**
1. Create new invitation
2. Increase expiration for future invitations

### Issue: Tenant Can't Register

**Solutions:**
1. Verify invitation status is `pending`
2. Check expiration date
3. Verify token is correct
4. Check if already accepted (single-use)

---

## Related Documentation

- **[API Endpoints - Owner](./03-api-endpoints-owner.md)**
- **[Invitation Types](./07-invitation-types.md)**
- **[Mail Configuration](./08-mail-configuration.md)**

