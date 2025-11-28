# New Database Tables Documentation

## Overview
Two new tables have been created to manage state release data for the Annual Action Plan module:

1. **state_release_generic** - Stores budget head categories
2. **state_release_data** - Stores actual state release data linked to budget heads

## Table Structures

### 1. state_release_generic
This table stores the budget head categories that will be used in the state release data.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Auto-increment primary key |
| allocation_name | varchar(255) | Name of the budget allocation category |
| status | boolean | Active status (1 = active, 0 = inactive) |
| created_at | timestamp | Record creation timestamp |
| updated_at | timestamp | Record update timestamp |

**Sample Data:**
- Total Allocation for FY 2025-26
- Allocation for FY 2025-26 (State Share)
- Allocation for FY 2025-26 (Center Share)
- Annual Allocation (Center Share) General Component
- Annual Allocation (Center Share) SCSP
- Annual Allocation (Center Share) TSP
- Annual Allocation (Center Share) Capital Assets - SCSP
- Annual Allocation (Center Share) Capital Assets - TSP
- Annual Allocation (Center Share) DAJUGA

### 2. state_release_data
This table stores the actual state release data linked to specific states, SLS components, and budget heads.

| Column | Type | Description | Foreign Key |
|--------|------|-------------|-------------|
| id | bigint | Auto-increment primary key | - |
| fy | varchar(255) | Financial year (e.g., 2025-26) | - |
| state_id | bigint | Reference to states table | states.id |
| SLS_id | bigint | Reference to pd_and_sls_comp table | pd_and_sls_comp.id |
| budget_head_id | bigint | Reference to state_release_generic table | state_release_generic.id |
| amount | decimal(15,5) | Amount with up to 5 decimal places | - |
| flag | boolean | Flag field (0 or 1) | - |
| isactive | boolean | Active status (0 or 1) | - |
| created_at | timestamp | Record creation timestamp | - |
| updated_at | timestamp | Record update timestamp | - |

## Relationships

- **state_release_data.state_id** → **states.id**
- **state_release_data.SLS_id** → **pd_and_sls_comp.id**
- **state_release_data.budget_head_id** → **state_release_generic.id**

## Usage

### Running Migrations
```bash
php artisan migrate
```

### Running Seeders
```bash
php artisan db:seed --class=StateReleaseGenericSeeder
```

### Example Queries

#### Get all budget heads
```php
$budgetHeads = StateReleaseGeneric::where('status', 1)->get();
```

#### Get state release data for a specific state and financial year
```php
$releaseData = StateReleaseData::with(['state', 'slsComponent', 'budgetHead'])
    ->where('state_id', $stateId)
    ->where('fy', $financialYear)
    ->get();
```

#### Get total allocation for a state
```php
$totalAllocation = StateReleaseData::where('state_id', $stateId)
    ->where('fy', $financialYear)
    ->where('budget_head_id', 1) // Total Allocation budget head
    ->sum('amount');
```

## Models

### StateReleaseGeneric Model
- **File:** `app/Models/StateReleaseGeneric.php`
- **Relationships:** Has many StateReleaseData records

### StateReleaseData Model
- **File:** `app/Models/StateReleaseData.php`
- **Relationships:** 
  - Belongs to State
  - Belongs to SlsPDComponent (SLS)
  - Belongs to StateReleaseGeneric (Budget Head)

## API Endpoints

The existing API endpoints can be extended to work with these new tables:

- `/api/aap-states` - Get states (already exists)
- `/api/aap-program-divisions` - Get program divisions (already exists)
- `/api/state-release-data` - Get state release data (to be implemented)
- `/api/budget-heads` - Get budget heads (to be implemented)

## Notes

- All foreign keys have cascade delete constraints
- Indexes are added for better query performance on frequently used columns
- The amount field supports up to 5 decimal places for precise financial calculations
- Boolean fields use 0/1 values for flag and isactive columns
