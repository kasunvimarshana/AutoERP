# Security Best Practices Guide

## Enterprise ERP/CRM SaaS Platform - Security Standards

This document outlines security best practices and requirements for the platform.

---

## Table of Contents

1. [Authentication & Authorization](#authentication--authorization)
2. [Data Protection](#data-protection)
3. [Input Validation & Sanitization](#input-validation--sanitization)
4. [Configuration Security](#configuration-security)
5. [Database Security](#database-security)
6. [API Security](#api-security)
7. [Deployment Security](#deployment-security)
8. [Security Monitoring](#security-monitoring)
9. [Incident Response](#incident-response)

---

## Authentication & Authorization

### JWT Token Security

**Requirements:**
- ✅ JWT_SECRET must be explicitly set in production (never use APP_KEY fallback)
- ✅ Token signing algorithm: HS256, HS384, or HS512 only
- ✅ Token TTL: Maximum 1 hour for access tokens
- ✅ Refresh token TTL: Maximum 24 hours
- ✅ Multi-device support with device tracking
- ✅ Token revocation mechanism enabled

**Generate Secure JWT Secret:**
```bash
php -r "echo base64_encode(random_bytes(32));"
```

**Configuration Validation:**
```bash
php artisan config:validate --production
```

### Password Security

**Requirements:**
- ✅ BCRYPT_ROUNDS minimum: 12 (recommended: 12-15)
- ✅ No default passwords in production
- ✅ Development seeders blocked in production environment
- ✅ Password reset with secure token expiration

**Seeder Security:**
```php
// Seeders automatically blocked in production
if (! app()->environment(['local', 'development', 'testing'])) {
    return; // Exit if not in dev environment
}
```

### Authorization

**RBAC/ABAC Implementation:**
- ✅ Policy-based authorization via Laravel policies
- ✅ Gate-based permission checks
- ✅ Tenant-scoped authorization
- ✅ Organization-level permissions with inheritance

**Example:**
```php
// In Controller
$this->authorize('update', $invoice);

// In Policy
public function update(User $user, Invoice $invoice): bool
{
    return $user->tenant_id === $invoice->tenant_id
        && $user->can('invoices.update');
}
```

---

## Data Protection

### Tenant Isolation

**Critical Requirements:**
- ✅ All queries automatically tenant-scoped
- ✅ No cross-tenant data access
- ✅ Tenant context validation on every request
- ✅ Foreign key constraints enforce tenant boundaries

**Implementation:**
```php
// TenantScoped trait applied to all models
use TenantScoped;

// Automatic query scoping
protected static function bootTenantScoped(): void
{
    static::addGlobalScope(new TenantScope);
}
```

### Data Encryption

**Requirements:**
- 🔒 Encrypt sensitive data at rest (PII, financial data)
- 🔒 TLS/HTTPS for all API communications
- 🔒 Database encryption for production deployments
- 🔒 Encrypted backups

### Personal Data Protection (GDPR/CCPA)

**Requirements:**
- 📋 Audit logging for all data access
- 📋 Data retention policies
- 📋 Right to erasure implementation
- 📋 Data export capabilities
- 📋 Consent management

---

## Input Validation & Sanitization

### Validation Rules

**All user inputs MUST be validated:**
```php
// Use FormRequest classes, not inline validation
class StoreInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
        ];
    }
}
```

### Input Sanitization

**Recommended Sanitization:**
```php
// Sanitize string inputs
'name' => ['required', 'string', 'max:255', new SanitizeHtml],
'description' => ['nullable', 'string', 'max:65535', new SanitizeHtml],

// Strip dangerous tags
use Illuminate\Support\Str;
$clean = Str::of($input)->trim()->stripTags();
```

### SQL Injection Prevention

**Always use:**
- ✅ Eloquent ORM with parameter binding
- ✅ Query builder with parameterized queries
- ❌ NEVER use raw SQL with user input

```php
// GOOD: Parameterized
$users = DB::table('users')->where('email', $email)->get();

// BAD: String concatenation
$users = DB::select("SELECT * FROM users WHERE email = '$email'");
```

### XSS Prevention

**Best Practices:**
- ✅ Escape all output in Blade templates: `{{ $variable }}`
- ✅ Use Content Security Policy (CSP) headers
- ✅ Sanitize rich text input
- ❌ NEVER use `{!! $variable !!}` with user input

---

## Configuration Security

### Environment Variables

**Critical Configurations:**
```bash
# Required in Production
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=<strong-random-secret>
JWT_REQUIRE_HTTPS=true

# Database credentials
DB_PASSWORD=<strong-password>

# Never commit secrets
# Use .env file or secret management service
```

### Configuration Validation

**Run before deployment:**
```bash
# Validate all required configs are set
php artisan config:validate --production --warnings

# Cache configuration in production
php artisan config:cache
```

### Secret Management

**Production Recommendations:**
- 🔐 Use AWS Secrets Manager, HashiCorp Vault, or similar
- 🔐 Rotate secrets regularly (quarterly minimum)
- 🔐 Use different secrets per environment
- 🔐 Never commit secrets to version control
- 🔐 Use .env.example as template only

---

## Database Security

### Query Security

**Requirements:**
- ✅ Parameterized queries only
- ✅ Foreign key constraints enabled
- ✅ Database-level permissions (least privilege)
- ✅ Read replicas for reporting queries

### Data Integrity

**Mechanisms:**
```php
// Transactions for data consistency
DB::transaction(function () {
    $invoice->update($data);
    $payment->create($paymentData);
});

// Optimistic locking
if ($model->version !== $expectedVersion) {
    throw new ConcurrencyException;
}

// Foreign key constraints in migrations
$table->foreign('customer_id')
    ->references('id')->on('customers')
    ->onDelete('restrict');
```

### Database Backups

**Requirements:**
- 📦 Automated daily backups
- 📦 Encrypted backup storage
- 📦 Backup retention: 30 days minimum
- 📦 Regular restore testing
- 📦 Point-in-time recovery capability

---

## API Security

### Rate Limiting

**Implementation:**
```php
// Apply to authentication endpoints
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Per-user rate limiting
RateLimiter::for('api', function (Request $request) {
    return Limit::perUser(1000)->perHour();
});
```

### CORS Configuration

**Production Settings:**
```php
// config/cors.php
'allowed_origins' => [env('APP_URL')],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

### API Versioning

**Best Practices:**
- 📌 Version all APIs: `/api/v1/...`
- 📌 Maintain backward compatibility
- 📌 Deprecation notices (6 months minimum)
- 📌 Documentation for each version

---

## Deployment Security

### Production Checklist

**Before Deployment:**
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] JWT_SECRET explicitly set
- [ ] All secrets in secure vault
- [ ] Database backups configured
- [ ] HTTPS/TLS enabled
- [ ] Security headers configured
- [ ] Error logging enabled
- [ ] Rate limiting active
- [ ] CSRF protection enabled (for web routes)

### Security Headers

**Recommended Headers:**
```php
// Middleware
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self'");
```

### Server Hardening

**Requirements:**
- 🔒 Firewall rules (allow only necessary ports)
- 🔒 Disable directory listing
- 🔒 Remove server signature headers
- 🔒 Keep software updated
- 🔒 Principle of least privilege for user permissions

---

## Security Monitoring

### Audit Logging

**What to Log:**
- ✅ Authentication attempts (success/failure)
- ✅ Authorization failures
- ✅ Data modifications (CRUD operations)
- ✅ Admin actions
- ✅ Configuration changes
- ✅ Sensitive data access

**Audit Log Structure:**
```php
[
    'user_id' => $user->id,
    'action' => 'invoice.created',
    'entity_type' => 'Invoice',
    'entity_id' => $invoice->id,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'changes' => $changes,
    'timestamp' => now(),
]
```

### Security Events

**Alert On:**
- 🚨 Multiple failed login attempts
- 🚨 Unusual access patterns
- 🚨 Privilege escalation attempts
- 🚨 Data exfiltration patterns
- 🚨 Configuration changes in production

### Monitoring Tools

**Recommended:**
- 📊 Application Performance Monitoring (APM)
- 📊 Log aggregation (ELK stack, Splunk)
- 📊 Intrusion Detection System (IDS)
- 📊 Database activity monitoring
- 📊 Uptime monitoring

---

## Incident Response

### Response Plan

**Steps:**
1. **Detect**: Monitor logs and alerts
2. **Contain**: Isolate affected systems
3. **Investigate**: Analyze attack vector
4. **Eradicate**: Remove threat
5. **Recover**: Restore from backups if needed
6. **Document**: Post-incident report

### Security Contacts

**Maintain:**
- 📞 Security team contact list
- 📞 Escalation procedures
- 📞 Vendor support contacts
- 📞 Legal/compliance contacts

### Breach Notification

**Requirements:**
- 📢 Notify affected users within 72 hours
- 📢 Report to relevant authorities (GDPR, CCPA)
- 📢 Document incident details
- 📢 Implement corrective measures

---

## Security Testing

### Regular Testing

**Schedule:**
- 🔍 Vulnerability scanning: Weekly
- 🔍 Penetration testing: Quarterly
- 🔍 Security audit: Annually
- 🔍 Dependency audits: Continuous (CI/CD)

### Testing Tools

```bash
# PHP dependency vulnerabilities
composer audit

# Static analysis
./vendor/bin/phpstan analyse

# Code quality
./vendor/bin/phpcs

# Security linting
./vendor/bin/security-checker security:check
```

---

## Compliance

### Standards

**Adherence to:**
- ✅ OWASP Top 10
- ✅ CWE/SANS Top 25
- ✅ PCI DSS (if handling payments)
- ✅ GDPR (if EU data)
- ✅ CCPA (if California users)
- ✅ SOC 2 Type II (enterprise customers)

### Data Classification

**Levels:**
- 🔴 **Critical**: PII, financial data, credentials
- 🟠 **Sensitive**: Business data, internal documents
- 🟢 **Public**: Marketing materials, public APIs

---

## Security Training

### Developer Requirements

**Mandatory Training:**
- 📚 Secure coding practices
- 📚 OWASP Top 10
- 📚 Authentication/authorization best practices
- 📚 Data protection regulations
- 📚 Incident response procedures

### Security Champions

**Program:**
- 👥 Designate security champions per team
- 👥 Regular security workshops
- 👥 Threat modeling sessions
- 👥 Security code reviews

---

## Appendix

### Security Checklist

```markdown
## Pre-Production Security Checklist

### Configuration
- [ ] APP_DEBUG=false
- [ ] JWT_SECRET set explicitly
- [ ] All secrets in vault
- [ ] HTTPS enforced
- [ ] BCRYPT_ROUNDS >= 12

### Authentication
- [ ] JWT expiration configured
- [ ] Multi-factor authentication (optional)
- [ ] Session timeout configured
- [ ] Password policy enforced

### Authorization
- [ ] All routes protected
- [ ] RBAC policies implemented
- [ ] Tenant isolation verified
- [ ] Admin access restricted

### Data Protection
- [ ] Encryption at rest enabled
- [ ] TLS 1.2+ for transit
- [ ] Database credentials rotated
- [ ] Backup encryption enabled

### Monitoring
- [ ] Audit logging enabled
- [ ] Error tracking configured
- [ ] Performance monitoring active
- [ ] Security alerts configured

### Testing
- [ ] Vulnerability scan passed
- [ ] Penetration test completed
- [ ] Load testing performed
- [ ] Backup restore tested
```

### Resources

**External References:**
- [OWASP Web Security Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- [Laravel Security Best Practices](https://laravel.com/docs/12.x/security)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [CWE Top 25](https://cwe.mitre.org/top25/)

