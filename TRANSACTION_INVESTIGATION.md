# Transaction Management Investigation

## Issue
After implementing smart transaction detection (`DB::transactionLevel() === 0`) across all services, 96 out of 129 tests fail with:
```
PDOException: There is already an active transaction
```

## Error Location
The error occurs in Laravel's `RefreshDatabase.php:101` when trying to begin a test transaction, indicating that a transaction is already active before the test starts.

## Investigation Findings

### ✅ Verified Working
1. **Syntax**: All PHP files pass `php -l` validation
2. **Balance**: All `DB::beginTransaction()` have matching `commit()` and `rollBack()` calls
3. **Wrapping**: All transaction operations are properly wrapped with `if ($shouldManageTransaction)` checks
4. **Logic**: The transaction detection logic is sound:
   ```php
   $shouldManageTransaction = DB::transactionLevel() === 0;
   
   try {
       if ($shouldManageTransaction) {
           DB::beginTransaction();
       }
       // ... operations ...
       if ($shouldManageTransaction) {
           DB::commit();
       }
   } catch (\Exception $e) {
       if ($shouldManageTransaction) {
           DB::rollBack();
       }
       throw $e;
   }
   ```

### ❓ Potential Issues

#### 1. Test Isolation
- **Hypothesis**: A transaction started in one test is not properly cleaned up before the next test
- **Evidence**: All 96 errors are identical, suggesting systematic issue
- **Counter**: Laravel's RefreshDatabase should handle test isolation automatically

#### 2. Application Bootstrap
- **Hypothesis**: A service is instantiated during app boot and starts a transaction
- **Evidence**: Error occurs during `parent::setUp()` before test code runs
- **Counter**: No service providers found that start transactions

#### 3. Race Conditions
- **Hypothesis**: Transaction state persists between test methods
- **Evidence**: Tests pass individually but fail when run together (unverified)
- **Counter**: Each test should be isolated with fresh database state

#### 4. RefreshDatabase Interaction
- **Hypothesis**: Our transaction detection interferes with RefreshDatabase's own transaction wrapping
- **Evidence**: Tests passed before our changes, fail after
- **Counter**: Our code should be transparent to RefreshDatabase since we only manage transactions when level is 0

## Test Results Timeline

| Commit | Status | Notes |
|--------|--------|-------|
| `2250d0d` (base) | ✅ Pass | Before our changes |
| `714743c` | ❌ Fail | First transaction fix attempt (BaseService only) |
| `c89be3b` | ❌ Fail | Extended fix to all module services |
| `f84e9eb` | ❌ Fail | Fixed syntax errors, transaction issues persist |

## Code Review

### BaseService Transaction Management
```php
public function create(array $data): mixed
{
    $shouldManageTransaction = DB::transactionLevel() === 0;
    
    try {
        if ($shouldManageTransaction) {
            DB::beginTransaction();
        }
        
        $record = $this->repository->create($data);
        
        if ($shouldManageTransaction) {
            DB::commit();
        }
        
        Log::info('Record created', [...]);
        
        return $record;
    } catch (\Exception $e) {
        if ($shouldManageTransaction) {
            DB::rollBack();
        }
        
        Log::error('Failed to create record', [...]);
        
        throw $e;
    }
}
```

**Analysis**: 
- ✅ Checks transaction level before starting
- ✅ Commits only if it started the transaction
- ✅ Rolls back only if it started the transaction
- ✅ Always propagates exceptions
- ✅ Logging happens after transaction management

### Module Service Transaction Management
All module services (AppointmentService, JobCardService, etc.) follow the same pattern for their custom methods.

## Diagnostic Questions

1. **Does the error occur for ALL tests or specific tests?**
   - Answer: 96 out of 129 tests (Organization and User modules primarily)

2. **Do tests pass when run individually?**
   - Unknown: Cannot test locally without dependencies

3. **What is the transaction level when RefreshDatabase tries to begin?**
   - Unknown: Need to add debugging output

4. **Is there a pattern to which tests fail?**
   - Tests 87-96 are Organization and User API tests
   - Tests 1-86 status unknown

## Recommended Solutions

### Option 1: Detect Test Environment
```php
$isTestEnvironment = app()->environment('testing');
$shouldManageTransaction = !$isTestEnvironment && DB::transactionLevel() === 0;
```
**Pros**: Simple, won't interfere with tests
**Cons**: Services won't manage transactions during tests at all

### Option 2: Check for RefreshDatabase
```php
$inRefreshDatabaseTransaction = DB::transactionLevel() > 0 && app()->environment('testing');
$shouldManageTransaction = !$inRefreshDatabaseTransaction && DB::transactionLevel() === 0;
```
**Pros**: More targeted, allows orchestrator transactions in tests
**Cons**: Assumes RefreshDatabase always uses level 1

### Option 3: Force Rollback in Test Teardown
```php
// In TestCase::tearDown()
while (DB::transactionLevel() > 0) {
    DB::rollBack();
}
```
**Pros**: Ensures clean state between tests
**Cons**: Masks the root cause

### Option 4: Remove Service-Level Transaction Management
```php
// Remove all transaction management from services
// Let orchestrators handle transactions exclusively
```
**Pros**: Simpler, fewer failure points
**Cons**: Services can't be used independently with transaction safety

## Next Steps

1. Add debug logging to capture transaction levels during test execution
2. Try Option 1 (detect test environment) as quick fix
3. If that works, investigate why it's needed and refine solution
4. Consider whether service-level transactions are necessary or if orchestrator-level is sufficient

## Related Files
- `app/Core/Services/BaseService.php` - Core transaction management
- `phpunit.xml` - Test configuration
- `Modules/*/app/Services/*.php` - Module services with transaction management
- Test files: `Modules/{Organization,User}/tests/Feature/*ApiTest.php`

## References
- [Laravel Database Transactions](https://laravel.com/docs/11.x/database#database-transactions)
- [Laravel Testing Database](https://laravel.com/docs/11.x/database-testing)
- [RefreshDatabase Trait](https://github.com/laravel/framework/blob/11.x/src/Illuminate/Foundation/Testing/RefreshDatabase.php)
