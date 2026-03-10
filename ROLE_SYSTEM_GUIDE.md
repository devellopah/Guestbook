# Role System Guide

## Overview

The new role system is based on PHP enum and provides type-safe user permission management.

## Roles

### Role::USER (1)
- Regular user
- Can view messages
- Can add own messages
- Can edit only own messages

### Role::ADMIN (2)
- Administrator
- All USER permissions
- Can edit any messages
- Can manage users
- Can change message status

## Usage

### Role Checking

```php
// Admin check
if ($this->checkAdmin()) {
    // Admin actions
}

// Role checking through enum (recommended way)
$role = \Core\Role::tryFrom($user['role']);
if ($role && $role->canEditAnyMessage()) {
    // Can edit any messages
}

if ($role && $role->canManageUsers()) {
    // Can manage users
}

// Or using User::getRole() method
$role = $user->getRole();
if ($role && $role->canEditAnyMessage()) {
    // Can edit any messages
}
```

### In Models

```php
// User model
$user = User::findById(1);
if ($user->isAdmin()) {
    // User is administrator
}

$userRole = $user->getRole();
$label = $userRole ? $userRole->label() : 'Unknown';
echo $label; // "Administrator" or "User"
```

### In Views

```php
// Display role in template
<?php if ($user['role'] == 2): ?>
    <span class="admin-badge">Admin</span>
<?php endif; ?>
```

## Advantages

1. **Type Safety** - enum guarantees valid values
2. **Readability** - clear method names
3. **Extensibility** - easy to add new roles
4. **Flexibility** - separate methods for different permissions

## Permission Checking Methods

- `canEditAnyMessage()` - can edit any messages
- `canManageUsers()` - can manage users
- `canToggleMessageStatus()` - can change message status
- `canDeleteMessages()` - can delete messages

## Migration

Existing data in the database remains unchanged:
- `role = 1` → USER
- `role = 2` → ADMIN
