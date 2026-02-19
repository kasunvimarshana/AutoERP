# Installation Notes

## After Cloning the Repository

When you clone this repository and run `composer install`, you need to manually enable modules:

### Enable All Modules

```bash
php artisan module:enable User
# or enable all modules at once
php artisan module:enable
```

### Verify Modules are Working

```bash
php artisan module:list
php artisan route:list --path=api
```

You should see the User module routes listed.

## Module Autoloading Issue

If you encounter "Class not found" errors for module providers after `composer install`, this is a known issue with the module autoloading process. To fix:

1. **Clear all caches:**
   ```bash
   rm -rf bootstrap/cache/*
   composer dump-autoload
   ```

2. **Enable the module:**
   ```bash
   php artisan module:enable User
   ```

3. **Verify it works:**
   ```bash
   php artisan route:list
   ```

## Alternative: Manual Module Activation

Edit `modules_statuses.json` in the root directory:

```json
{
    "User": true
}
```

Then run:
```bash
composer dump-autoload
php artisan optimize:clear
```

This ensures all modules are properly registered and autoloaded.
