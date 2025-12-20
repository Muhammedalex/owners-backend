# Settings Update Guide - Frontend Documentation

## Overview

This guide explains all the ways to update settings in the system. The settings system supports multiple update methods, each designed for different use cases.

---

## Table of Contents

1. [Update Methods](#update-methods)
2. [Value Types](#value-types)
3. [Update Single Setting](#update-single-setting)
4. [Bulk Update Settings](#bulk-update-settings)
5. [Permissions & Scopes](#permissions--scopes)
6. [Examples](#examples)
7. [Error Handling](#error-handling)

---

## Update Methods

There are **3 main ways** to update settings:

1. **Single Update** - Update one setting by ID
2. **Bulk Update** - Update multiple settings at once
3. **Update by Key** - Update using the setting key (via bulk or single)

---

## Value Types

Settings support different value types. The system automatically converts values based on `value_type`:

| Type | Description | Example Values | Storage Format |
|------|-------------|----------------|----------------|
| `string` | Text value | `"Hello World"`, `"123"` | Stored as string |
| `integer` | Whole number | `100`, `-50`, `0` | Stored as string, returned as integer |
| `decimal` | Decimal number | `99.99`, `100.5` | Stored as string, returned as float |
| `boolean` | True/False | `true`, `false`, `1`, `0` | Stored as `"1"` or `"0"`, returned as boolean |
| `json` | JSON object | `{"key": "value"}` | Stored as JSON string, returned as object |
| `array` | Array/List | `[1, 2, 3]` | Stored as JSON string, returned as array |

**Important:** Always specify the correct `value_type` when updating. The system will convert the value automatically.

---

## Update Single Setting

### Endpoint
```
PUT /api/v1/settings/{setting_id}
```

### Headers
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
Cookie: ownership_uuid={uuid}  // Required for ownership-specific settings
```

### Request Body

All fields are **optional** - only include fields you want to update:

```json
{
  "value": "new_value",
  "value_type": "string",
  "group": "financial",
  "description": "Updated description"
}
```

### Field Details

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `value` | mixed | No | The new value for the setting |
| `value_type` | string | No | Type of value: `string`, `integer`, `decimal`, `boolean`, `json`, `array` |
| `group` | string | No | Setting group (e.g., `financial`, `contract`, `invoice`) |
| `description` | string | No | Description of the setting |

### Notes

- If `value` is provided without `value_type`, the existing `value_type` is used
- If `value_type` is provided, it will be used to convert the value
- You can update only `description` or `group` without changing the value
- The `key` and `ownership_id` **cannot** be changed after creation

### Example: Update Value Only

```javascript
// Update just the value (keeps existing value_type)
const response = await fetch('/api/v1/settings/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    value: "New Value"
  })
});
```

### Example: Update Value with Type

```javascript
// Update value and specify type
const response = await fetch('/api/v1/settings/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    value: 100,
    value_type: "integer"
  })
});
```

### Example: Update Description Only

```javascript
// Update description without changing value
const response = await fetch('/api/v1/settings/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    description: "Updated description text"
  })
});
```

### Example: Update Multiple Fields

```javascript
// Update value, type, and description
const response = await fetch('/api/v1/settings/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    value: true,
    value_type: "boolean",
    description: "Enable/disable feature"
  })
});
```

### Response

```json
{
  "success": true,
  "message": "Setting updated successfully.",
  "data": {
    "id": 1,
    "ownership_id": 1,
    "key": "setting_key",
    "value": "new_value",
    "value_type": "string",
    "group": "financial",
    "description": "Updated description",
    "is_system_wide": false,
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-15T12:00:00.000000Z"
  }
}
```

---

## Bulk Update Settings

### Endpoint
```
PUT /api/v1/settings/bulk
```

### Headers
```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
Cookie: ownership_uuid={uuid}  // Required
```

### Request Body

```json
{
  "settings": [
    {
      "key": "setting_key_1",
      "value": "value1",
      "value_type": "string",
      "group": "financial",
      "description": "Description 1"
    },
    {
      "key": "setting_key_2",
      "value": 100,
      "value_type": "integer",
      "group": "contract",
      "description": "Description 2"
    }
  ]
}
```

### Field Details

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `settings` | array | Yes | Array of setting objects to update |
| `settings[].key` | string | Yes | Setting key (must exist) |
| `settings[].value` | mixed | No | New value for the setting |
| `settings[].value_type` | string | Yes | Type: `string`, `integer`, `decimal`, `boolean`, `json`, `array` |
| `settings[].group` | string | Yes | Setting group |
| `settings[].description` | string | No | Setting description |

### Notes

- **Creates or Updates**: If a setting with the key doesn't exist, it will be created. If it exists, it will be updated.
- **Ownership Scope**: All settings in bulk update are created/updated for the current ownership (from cookie).
- **Transaction**: All updates happen in a transaction - if one fails, all are rolled back.
- **Permissions**: You need `settings.{group}.update` permission for each group in the request.

### Example: Bulk Update Multiple Settings

```javascript
const response = await fetch('/api/v1/settings/bulk', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    settings: [
      {
        key: "vat_rate",
        value: 15,
        value_type: "decimal",
        group: "financial",
        description: "VAT percentage rate"
      },
      {
        key: "auto_generate_invoices",
        value: true,
        value_type: "boolean",
        group: "invoice",
        description: "Auto-generate invoices on contract start"
      },
      {
        key: "contract_notification_days",
        value: 30,
        value_type: "integer",
        group: "contract",
        description: "Days before contract expiry to send notification"
      }
    ]
  })
});
```

### Response

```json
{
  "success": true,
  "message": "Settings updated successfully.",
  "data": [
    {
      "id": 1,
      "ownership_id": 1,
      "key": "vat_rate",
      "value": 15,
      "value_type": "decimal",
      "group": "financial",
      "description": "VAT percentage rate",
      "is_system_wide": false
    },
    {
      "id": 2,
      "ownership_id": 1,
      "key": "auto_generate_invoices",
      "value": true,
      "value_type": "boolean",
      "group": "invoice",
      "description": "Auto-generate invoices on contract start",
      "is_system_wide": false
    }
  ]
}
```

---

## Value Type Examples

### String

```javascript
{
  "value": "Hello World",
  "value_type": "string"
}
// Returns: "Hello World"
```

### Integer

```javascript
{
  "value": 100,
  "value_type": "integer"
}
// Returns: 100 (as number)
```

### Decimal

```javascript
{
  "value": 99.99,
  "value_type": "decimal"
}
// Returns: 99.99 (as float)
```

### Boolean

```javascript
{
  "value": true,
  "value_type": "boolean"
}
// Returns: true (as boolean)

// Or
{
  "value": false,
  "value_type": "boolean"
}
// Returns: false (as boolean)
```

### JSON Object

```javascript
{
  "value": {
    "key1": "value1",
    "key2": 123
  },
  "value_type": "json"
}
// Returns: { key1: "value1", key2: 123 } (as object)
```

### Array

```javascript
{
  "value": [1, 2, 3, "four"],
  "value_type": "array"
}
// Returns: [1, 2, 3, "four"] (as array)
```

---

## Permissions & Scopes

### System-Wide Settings

- **Scope**: `scope=system` in query parameter
- **Permission**: `settings.system.update` (Super Admin only)
- **Ownership**: `null` (no ownership_id)

### Ownership-Specific Settings

- **Scope**: `scope=ownership` (default) or from cookie
- **Permission**: `settings.{group}.update` (e.g., `settings.financial.update`)
- **Ownership**: Current ownership from cookie (`ownership_uuid`)

### Permission Check

The system checks permissions based on the setting's `group`:

- `settings.financial.update` - For financial settings
- `settings.contract.update` - For contract settings
- `settings.invoice.update` - For invoice settings
- `settings.tenant.update` - For tenant settings
- `settings.system.update` - For system-wide settings (Super Admin only)

---

## Complete Examples

### Example 1: Update Contract Settings Form

```javascript
// Form submission handler
async function updateContractSettings(formData) {
  const settings = [
    {
      key: "contract_auto_renew",
      value: formData.autoRenew,
      value_type: "boolean",
      group: "contract",
      description: "Automatically renew contracts"
    },
    {
      key: "contract_notification_days",
      value: parseInt(formData.notificationDays),
      value_type: "integer",
      group: "contract",
      description: "Days before expiry to notify"
    },
    {
      key: "contract_default_duration",
      value: parseFloat(formData.defaultDuration),
      value_type: "decimal",
      group: "contract",
      description: "Default contract duration in years"
    }
  ];

  try {
    const response = await fetch('/api/v1/settings/bulk', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ settings })
    });

    const result = await response.json();
    
    if (result.success) {
      console.log('Settings updated successfully');
      return result.data;
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Error updating settings:', error);
    throw error;
  }
}
```

### Example 2: Update Single Setting with Type Conversion

```javascript
async function updateVATRate(newRate) {
  // First, get the current setting to know its ID
  const getResponse = await fetch('/api/v1/settings/key/vat_rate', {
    headers: {
      'Authorization': `Bearer ${token}`,
    }
  });
  
  const setting = await getResponse.json();
  
  // Update the setting
  const updateResponse = await fetch(`/api/v1/settings/${setting.data.id}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      value: parseFloat(newRate),
      value_type: "decimal"
    })
  });

  return await updateResponse.json();
}
```

### Example 3: Update Setting Description Only

```javascript
async function updateSettingDescription(settingId, newDescription) {
  const response = await fetch(`/api/v1/settings/${settingId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      description: newDescription
    })
  });

  return await response.json();
}
```

### Example 4: React Hook for Settings Update

```javascript
import { useState } from 'react';

function useUpdateSetting() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const updateSetting = async (settingId, updates) => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`/api/v1/settings/${settingId}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(updates)
      });

      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message);
      }

      return result.data;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const bulkUpdate = async (settings) => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch('/api/v1/settings/bulk', {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ settings })
      });

      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message);
      }

      return result.data;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { updateSetting, bulkUpdate, loading, error };
}
```

---

## Error Handling

### Common Errors

#### 403 Forbidden - Permission Denied

```json
{
  "success": false,
  "message": "You don't have permission to update settings in this group."
}
```

**Solution**: Check user permissions for the setting's group.

#### 400 Bad Request - Ownership Required

```json
{
  "success": false,
  "message": "Ownership scope is required."
}
```

**Solution**: Ensure `ownership_uuid` cookie is set.

#### 404 Not Found - Setting Not Found

```json
{
  "success": false,
  "message": "Setting not found."
}
```

**Solution**: Verify the setting ID exists.

#### 422 Validation Error

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "value_type": ["The value type must be one of: string, integer, decimal, boolean, json, array."]
  }
}
```

**Solution**: Check validation rules and ensure all required fields are provided.

### Error Handling Example

```javascript
async function updateSettingSafely(settingId, updates) {
  try {
    const response = await fetch(`/api/v1/settings/${settingId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(updates)
    });

    const result = await response.json();

    if (!response.ok) {
      // Handle different error types
      if (response.status === 403) {
        throw new Error('Permission denied. You cannot update this setting.');
      } else if (response.status === 404) {
        throw new Error('Setting not found.');
      } else if (response.status === 422) {
        // Validation errors
        const errors = Object.values(result.errors || {}).flat();
        throw new Error(errors.join(', '));
      } else {
        throw new Error(result.message || 'Failed to update setting');
      }
    }

    return result.data;
  } catch (error) {
    console.error('Update failed:', error);
    // Show user-friendly error message
    alert(error.message);
    throw error;
  }
}
```

---

## Best Practices

1. **Use Bulk Update for Multiple Settings**: More efficient than multiple single updates
2. **Always Specify value_type**: Ensures correct type conversion
3. **Handle Errors Gracefully**: Show user-friendly error messages
4. **Validate on Frontend**: Check value types before sending
5. **Use Transactions**: Bulk updates are atomic (all or nothing)
6. **Cache Considerations**: Settings cache is automatically cleared on update

---

## Summary

- **Single Update**: `PUT /api/v1/settings/{id}` - Update one setting
- **Bulk Update**: `PUT /api/v1/settings/bulk` - Update multiple settings
- **Value Types**: `string`, `integer`, `decimal`, `boolean`, `json`, `array`
- **Permissions**: Required based on setting group
- **Ownership**: Required for ownership-specific settings (from cookie)
- **Partial Updates**: Only send fields you want to change

For more information, see the main API documentation.

