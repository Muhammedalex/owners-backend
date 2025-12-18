# Workflow - Tenant Side

## Overview

This document describes the workflow for tenants to receive and accept invitations.

---

## Receiving Invitation

### Scenario 1: Email Invitation

**Use Case:** Tenant receives invitation via email

#### Email Content

**Subject:** `You're invited to register as a tenant - {Ownership Name}`

**Body:**
```
Dear {Name or "Future Tenant"},

You have been invited by {Ownership Name} to register as a tenant 
in their property management system.

Click the link below to complete your registration:
{Registration Link}

This link will expire on {Expiration Date}.

If you did not expect this invitation, please ignore this email.

Best regards,
{Ownership Name}
```

#### Steps

1. **Receive Email**
   - Tenant receives email from ownership
   - Email contains invitation link

2. **Click Link**
   - Link format: `https://app.example.com/register/tenant?token=abc123...`
   - Redirects to registration page

---

### Scenario 2: Public Link

**Use Case:** Tenant finds invitation link on website/social media

#### Steps

1. **Find Link**
   - Tenant sees link on website
   - Or receives link from friend/colleague

2. **Click Link**
   - Same link format
   - Redirects to registration page

---

## Registration Process

### Step 1: Token Validation

**What Happens:**
- Frontend calls validation endpoint
- System checks token validity
- Returns invitation details

**Possible Outcomes:**

✅ **Valid Token**
```json
{
  "valid": true,
  "invitation": {
    "email": "tenant@example.com",
    "name": "Ahmed Ali",
    "ownership": { "name": "ABC Real Estate" },
    "expires_at": "2025-12-22 10:00:00"
  }
}
```

❌ **Invalid Token**
- Error: "Invalid invitation token"
- Show error page

❌ **Expired Token**
- Error: "Invitation has expired"
- Show expiration message
- Option to contact owner

❌ **Already Accepted** (Single-use)
- Error: "Invitation has already been accepted"
- Show message
- Option to login if already registered

❌ **Cancelled**
- Error: "Invitation has been cancelled"
- Show message
- Option to contact owner

---

### Step 2: Fill Registration Form

**Form Fields:**

#### Personal Information
- **First Name** (required)
- **Last Name** (required)
- **Email** (required, pre-filled if available)
- **Phone** (optional)

#### Account Security
- **Password** (required, min 8 chars)
- **Password Confirmation** (required)

#### Identity Information
- **National ID** (optional)
- **ID Type** (optional): `national_id`, `iqama`, `passport`, `commercial_registration`
- **ID Expiry Date** (optional)

#### Emergency Contact
- **Emergency Name** (optional)
- **Emergency Phone** (optional)
- **Emergency Relation** (optional)

#### Employment Information
- **Employment Status** (optional): `employed`, `self_employed`, `unemployed`, `retired`, `student`
- **Employer** (optional)
- **Income** (optional)

#### Additional
- **Notes** (optional)

**Pre-filled Data:**
- Email (if invitation has email)
- Name (if invitation has name)

---

### Step 3: Submit Registration

**What Happens:**

1. **Frontend Validation**
   - Validates all required fields
   - Checks password strength
   - Validates email format
   - Validates phone format (Saudi)

2. **API Request**
   ```
   POST /api/v1/public/tenant-invitations/{token}/accept
   ```

3. **Backend Processing**
   - Validates token again
   - Checks expiration
   - Validates email match (single-use)
   - Creates/updates user account
   - Sets user type: `tenant`
   - Assigns role: `Tenant`
   - Creates tenant profile
   - Links to ownership
   - Updates invitation status
   - Generates auth tokens

4. **Response**
   ```json
   {
     "success": true,
     "data": {
       "user": { ... },
       "tenant": { ... },
       "access_token": "...",
       "redirect_to": "/dashboard"
     }
   }
   ```

---

### Step 4: Post-Registration

**What Happens:**

1. **Token Storage**
   - Frontend saves `access_token`
   - Stores in localStorage/sessionStorage

2. **Redirect**
   - Redirects to `/dashboard`
   - User is automatically logged in

3. **Email Verification** (if enabled)
   - Email verification sent
   - User must verify email
   - Some features may be restricted until verified

---

## Registration Scenarios

### Scenario 1: New User Registration

**Case:** User doesn't have an account yet

**Process:**
1. User fills registration form
2. System creates new user account
3. Sets type: `tenant`
4. Assigns role: `Tenant`
5. Creates tenant profile
6. Links to ownership
7. User can login immediately

---

### Scenario 2: Existing User Registration

**Case:** User already has an account (different email or same email)

**Process:**

**If Same Email:**
1. System finds existing user
2. Checks if tenant already exists for this ownership
3. If exists → Error: "Tenant already exists"
4. If not exists → Creates tenant profile
5. Updates user type to `tenant` (if not already)
6. Assigns `Tenant` role (if not already)
7. Links to ownership

**If Different Email:**
1. System creates new user account
2. Continues with normal registration flow

---

### Scenario 3: Multi-use Invitation

**Case:** Multiple tenants using same invitation link

**Process:**
1. First tenant registers → Creates tenant, invitation stays `pending`
2. Second tenant registers → Creates tenant, invitation stays `pending`
3. Third tenant registers → Creates tenant, invitation stays `pending`
4. All tenants linked to same invitation via `invitation_id`
5. Owner can see all tenants who joined
6. Owner manually closes invitation when done

---

## Error Handling

### Common Errors

#### 1. Email Mismatch (Single-use)

**Error:** "Email does not match invitation."

**Cause:** Registration email doesn't match invitation email

**Solution:** Use the email address from the invitation

---

#### 2. Tenant Already Exists

**Error:** "Tenant already exists for this ownership."

**Cause:** User already registered as tenant for this ownership

**Solution:** Login with existing account

---

#### 3. Password Too Weak

**Error:** Validation error for password

**Cause:** Password doesn't meet requirements

**Solution:** Use stronger password (min 8 chars, recommended: uppercase, lowercase, numbers, symbols)

---

#### 4. Invalid Phone Format

**Error:** Validation error for phone

**Cause:** Phone number not in Saudi format

**Solution:** Use format: `+966501234567` or `0501234567`

---

## User Experience Flow

### Successful Registration Flow

```
1. Click invitation link
   ↓
2. See registration page
   ↓
3. Form pre-filled with email/name (if available)
   ↓
4. Fill remaining fields
   ↓
5. Submit form
   ↓
6. See success message
   ↓
7. Automatically logged in
   ↓
8. Redirected to dashboard
```

### Error Flow

```
1. Click invitation link
   ↓
2. See error message (expired/invalid/cancelled)
   ↓
3. Option to contact owner
   ↓
4. Or request new invitation
```

---

## Frontend Implementation Guide

### Page Structure

```
/register/tenant?token={token}
├── Token Validation (on load)
├── Registration Form
│   ├── Personal Info Section
│   ├── Account Security Section
│   ├── Identity Section
│   ├── Emergency Contact Section
│   └── Employment Section
└── Submit Button
```

### Validation Flow

```javascript
// 1. Validate token on page load
validateToken(token)
  .then(data => {
    if (data.valid) {
      prefillForm(data.invitation);
      showForm();
    } else {
      showError(data.message);
    }
  });

// 2. Validate form before submit
validateForm()
  .then(valid => {
    if (valid) {
      submitRegistration();
    }
  });

// 3. Submit registration
submitRegistration()
  .then(data => {
    saveToken(data.access_token);
    redirect(data.redirect_to);
  })
  .catch(error => {
    showErrors(error.errors);
  });
```

---

## Security Considerations

### For Tenants

1. **Token Security**
   - Don't share invitation link
   - Link is single-use (for email invitations)
   - Link expires after set time

2. **Password Security**
   - Use strong password
   - Don't reuse passwords
   - Keep password secure

3. **Email Verification**
   - Verify email if required
   - Check spam folder for verification email

---

## Related Documentation

- **[API Endpoints - Public](./04-api-endpoints-public.md)**
- **[Invitation Types](./07-invitation-types.md)**
- **[User Registration Flow](./10-user-registration-flow.md)**

