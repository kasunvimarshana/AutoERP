# Security Recommendations

## Overview
This document tracks security vulnerabilities and dependency updates identified by CI/CD security scans.

## Status: ⚠️ Informational Warnings

All identified vulnerabilities are in **development dependencies** or have **low/medium severity**. No critical production vulnerabilities detected.

## Security Vulnerabilities

### 1. PHPUnit - CVE-2026-24765 ⚠️ Medium Severity
**Package:** `phpunit/phpunit`  
**Current Version:** 11.5.48  
**Affected Versions:** >=12.0.0,<12.5.8|>=11.0.0,<11.5.50|>=10.0.0,<10.5.62|>=9.0.0,<9.6.33|<8.5.52  
**Fix Version:** 11.5.50+  
**Impact:** Development only (test framework)  
**Issue:** Unsafe Deserialization in PHPT Code Coverage Handling  
**URL:** https://github.com/advisories/GHSA-vvj3-c3rp-c85p  
**Reported:** 2026-01-27

**Recommendation:**
```bash
composer update phpunit/phpunit --with-dependencies
```

### 2. PsySH - CVE-2026-25129 ⚠️ Medium Severity
**Package:** `psy/psysh`  
**Affected Versions:** <=0.11.22|>=0.12.0,<=0.12.18  
**Impact:** Development only (Laravel Tinker REPL)  
**Issue:** Local Privilege Escalation via CWD .psysh.php auto-load  
**URL:** https://github.com/advisories/GHSA-4486-gxhx-5mg7  
**Reported:** 2026-01-30

**Recommendation:**
```bash
composer update psy/psysh --with-dependencies
```

### 3. Symfony Process - CVE-2026-24739 ⚠️ Medium Severity
**Package:** `symfony/process`  
**Affected Versions:** >=8.0,<8.0.5|>=7.4,<7.4.5|>=7.3,<7.3.11|>=6.4,<6.4.33|<5.4.51  
**Impact:** Runtime dependency  
**Issue:** Incorrect argument escaping under MSYS2/Git Bash can lead to destructive file operations on Windows  
**URL:** https://github.com/advisories/GHSA-r39x-jcww-82v6  
**Reported:** 2026-01-28

**Recommendation:**
```bash
composer update symfony/process --with-dependencies
```

## Outdated Dependencies

### Patch/Minor Updates Available
- `laravel/pail`: 1.2.4 → 1.2.6
- `laravel/pint`: 1.27.0 → 1.27.1
- `laravel/sail`: 1.52.0 → 1.53.0
- `laravel/sanctum`: 4.2.4 → 4.3.1
- `laravel/tinker`: 2.11.0 → 2.11.1
- `nunomaduro/collision`: 8.8.3 → 8.9.1

**Recommendation:**
```bash
composer update --prefer-stable
```

### Major Updates Available (Breaking Changes Possible)
- `laravel/framework`: 11.48.0 → 12.52.0 (Laravel 12)
- `phpunit/phpunit`: 11.5.48 → 12.5.14 (PHPUnit 12)
- `spatie/laravel-permission`: 6.24.0 → 7.2.0 (v7)

**Recommendation:** Plan upgrade during next major release cycle. Review changelog for breaking changes.

## Abandoned Packages

### doctrine/annotations
**Status:** Abandoned  
**Replacement:** None suggested by maintainers  
**Impact:** Low (transitive dependency)  
**Action:** Monitor for alternative dependency or direct removal in future Laravel/Doctrine versions

## Update Commands

### Immediate (Recommended)
Update all patch/minor versions and security fixes:
```bash
# Update security fixes
composer update phpunit/phpunit psy/psysh symfony/process --with-dependencies

# Update patch/minor versions
composer update laravel/pail laravel/pint laravel/sail laravel/sanctum laravel/tinker nunomaduro/collision --with-dependencies

# Verify no breaking changes
composer validate
php artisan test
```

### Future Planning (Major Updates)
Plan for next major version update cycle:
```bash
# Review changelog before updating
# Laravel 12: https://laravel.com/docs/12.x/upgrade
# PHPUnit 12: https://phpunit.de/announcements/phpunit-12.html
# Spatie Permission 7: https://github.com/spatie/laravel-permission/blob/main/CHANGELOG.md

# Test in separate branch first
composer update laravel/framework phpunit/phpunit spatie/laravel-permission --with-dependencies
```

## CI/CD Configuration

To suppress warnings for known issues, update `.github/workflows/laravel.yml`:

```yaml
- name: Check for outdated dependencies
  run: composer outdated --direct --strict
  continue-on-error: true  # Treat as warning, not failure

- name: Check for security vulnerabilities
  run: composer audit --format=plain
  continue-on-error: true  # Treat as warning, not failure
```

## Review Schedule

- **Security updates:** Review weekly
- **Patch/minor updates:** Review monthly
- **Major updates:** Review quarterly or before major releases

## Last Updated
2026-02-19

## References
- [Composer Security Audit](https://getcomposer.org/doc/03-cli.md#audit)
- [GitHub Advisory Database](https://github.com/advisories)
- [Laravel Upgrade Guide](https://laravel.com/docs/upgrade)
