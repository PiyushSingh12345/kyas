# Role-Based Access Control (RBAC) System

## Overview

The KYAS application implements a comprehensive role-based access control system that restricts page access and functionality based on user types. The system uses the relationship between `users.user_type_id` and `md_user_types.md_user_type_id`.

## User Types

The system supports 5 different user types with specific permissions:

### 1. KY_Admin (ID: 1)
- **Full Access**: Complete system access
- **Permissions**: 
  - User Management (Create, Update, Delete users)
  - All Budget Head operations
  - All Budget Allocation operations
  - All Mother Sanction operations
  - All Daily Sanction operations
  - All Re-Appropriation operations
  - All Annual Action Plan operations
  - All Reports and Dashboards

### 2. KY User (ID: 2)
- **Primary User**: Main operational user
- **Permissions**:
  - Budget Head operations
  - Budget Allocation operations
  - Mother Sanction operations
  - Daily Sanction operations
  - Re-Appropriation operations
  - Annual Action Plan operations
  - Reports and Dashboards
  - **Cannot**: Manage users

### 3. Master Data User (ID: 3)
- **Data Management**: Focused on master data operations
- **Permissions**:
  - Budget Head operations
  - Budget Phase operations
  - State/UTs operations
  - PD Component/SLS operations
  - Reports viewing
  - **Cannot**: Access operational modules (Mother Sanction, Daily Sanction, etc.)

### 4. PD Viewer (ID: 4)
- **Read-Only Access**: Limited to viewing reports
- **Permissions**:
  - Reports and Dashboards only
  - **Cannot**: Access any operational modules

### 5. CSNA User (ID: 5)
- **Limited Access**: Minimal system access
- **Permissions**: 
  - Basic dashboard access
  - **Cannot**: Access most operational modules

## Implementation Details

### Backend Implementation

#### 1. Middleware (`CheckUserRole.php`)
```php
// Usage in routes
Route::get('/user-create', function () {
    return Inertia::render('User_management/createUser');
})->middleware(['auth', 'verified', 'role:1'])->name('user-create');
```

#### 2. Route Protection
All routes are protected with role-based middleware:
- `role:1` - Only KY_Admin
- `role:2` - Only KY User
- `role:2,3` - KY User or Master Data User
- `role:1,2,4` - KY_Admin, KY User, or PD Viewer

#### 3. User Model Relationship
```php
public function userType()
{
    return $this->belongsTo(MdUserType::class, 'user_type_id', 'md_user_type_id');
}
```

### Frontend Implementation

#### 1. Vue Composable (`useRoleAccess.js`)
```javascript
import { useRoleAccess } from '../Composables/useRoleAccess'

const { hasRole, canAccessUserManagement, isAdmin } = useRoleAccess()
```

#### 2. Role Guard Component (`RoleGuard.vue`)
```vue
<RoleGuard :roles="[1]" show-fallback>
  <div>Admin only content</div>
</RoleGuard>
```

#### 3. Sidebar Navigation
The sidebar automatically shows/hides menu items based on user roles:
```vue
<li v-if="hasRole([1])">
  <!-- Admin only menu -->
</li>
```

## Page Access Matrix

| Page/Module | KY_Admin | KY User | Master Data User | PD Viewer | CSNA User |
|-------------|----------|---------|------------------|-----------|-----------|
| User Management | ✅ | ❌ | ❌ | ❌ | ❌ |
| Budget Heads | ✅ | ✅ | ✅ | ❌ | ❌ |
| Budget Allocation | ✅ | ✅ | ❌ | ❌ | ❌ |
| Mother Sanction | ✅ | ✅ | ❌ | ❌ | ❌ |
| Daily Sanction | ✅ | ✅ | ❌ | ❌ | ❌ |
| Re-Appropriation | ✅ | ✅ | ❌ | ❌ | ❌ |
| Annual Action Plan | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reports | ✅ | ✅ | ✅ | ✅ | ❌ |

## API Endpoints Protection

All API endpoints are protected with the same role-based middleware:

```php
Route::post('/budget-heads', [BudgetHeadController::class, 'store'])
    ->middleware(['role:2,3'])
    ->name('BudgetHead.store');
```

## Usage Examples

### 1. Protecting a Route
```php
Route::get('/sensitive-page', function () {
    return Inertia::render('SensitivePage');
})->middleware(['auth', 'verified', 'role:1,2'])->name('sensitive-page');
```

### 2. Frontend Role Check
```vue
<template>
  <div v-if="hasRole([1, 2])">
    <button>Edit Data</button>
  </div>
  <div v-else-if="hasRole([4])">
    <span>View Only</span>
  </div>
</template>

<script setup>
import { useRoleAccess } from '../Composables/useRoleAccess'
const { hasRole } = useRoleAccess()
</script>
```

### 3. Using Role Guard Component
```vue
<template>
  <RoleGuard :roles="[1]" show-fallback>
    <AdminPanel />
  </RoleGuard>
  
  <RoleGuard :roles="[2, 3]">
    <DataManagementPanel />
  </RoleGuard>
</template>
```

## Security Features

1. **Server-Side Protection**: All routes are protected at the server level
2. **Frontend Hiding**: UI elements are hidden for unauthorized users
3. **API Protection**: All API endpoints require proper role access
4. **Graceful Degradation**: Users see appropriate messages when access is denied

## Error Handling

When a user tries to access a restricted page:
1. Middleware redirects to dashboard
2. Error message is displayed: "You do not have permission to access this page."
3. User is logged and redirected appropriately

## Maintenance

To add new roles or modify permissions:
1. Update the `md_user_types` table
2. Modify the middleware logic in `CheckUserRole.php`
3. Update route middleware assignments
4. Modify the frontend composable if needed
5. Update this documentation

## Database Schema

```sql
-- Users table
users.user_type_id -> comma-separated list of md_user_type_id values

-- Master data table
md_user_types.md_user_type_id -> unique identifier for user type
md_user_types.user_type_name -> human-readable name
md_user_types.is_active -> whether the role is active
```

This system ensures that users can only access the functionality appropriate to their role, maintaining security and proper access control throughout the application.
