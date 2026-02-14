# AutoERP Security Documentation

## Overview

This document outlines the security architecture, best practices, and compliance measures implemented in AutoERP.

## Table of Contents

1. [Security Architecture](#security-architecture)
2. [Authentication & Authorization](#authentication--authorization)
3. [Data Protection](#data-protection)
4. [Network Security](#network-security)
5. [Application Security](#application-security)
6. [Multi-Tenancy Security](#multi-tenancy-security)
7. [Audit & Logging](#audit--logging)
8. [Compliance](#compliance)
9. [Incident Response](#incident-response)
10. [Security Best Practices](#security-best-practices)

## Security Architecture

### Defense in Depth

AutoERP implements multiple layers of security:

```
┌─────────────────────────────────────────────────────────┐
│  Layer 1: Network Security (Firewall, DDoS Protection) │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│  Layer 2: Transport Security (TLS/SSL)                 │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│  Layer 3: Application Security (Auth, Validation)      │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│  Layer 4: Data Security (Encryption, Access Control)   │
└────────────────┬────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────┐
│  Layer 5: Monitoring & Response (SIEM, Alerts)         │
└─────────────────────────────────────────────────────────┘
```

## Authentication & Authorization

### Authentication Methods

#### 1. Token-Based Authentication (API)

Uses Laravel Sanctum for API authentication:

```php
// Generate token
$token = $user->createToken('api-token')->plainTextToken;

// Token format
Authorization: Bearer {token}
```

**Security Features:**
- Tokens are hashed before storage
- Configurable expiration (default: 24 hours)
- Per-user token revocation
- Rate limiting per token

#### 2. Session-Based Authentication (Web)

Uses Laravel's built-in session authentication:

- Secure, HTTP-only cookies
- CSRF protection on all state-changing operations
- Session fixation protection
- Configurable session lifetime

#### 3. Multi-Factor Authentication (MFA)

**Supported Methods:**
- TOTP (Time-based One-Time Password) - Google Authenticator, Authy
- SMS-based OTP
- Email-based OTP
- Backup codes for account recovery

**Implementation:**
```php
// Enable MFA
$user->enableTwoFactorAuthentication();

// Verify OTP
$user->verifyTwoFactorCode($code);
```

### Authorization

#### Role-Based Access Control (RBAC)

Implemented using Spatie Laravel Permission:

**Default Roles:**
- **Super Admin**: Full system access
- **Admin**: Tenant-wide administrative access
- **Manager**: Department/branch management
- **User**: Standard user access
- **Guest**: Read-only access

**Permission Structure:**
```
{resource}.{action}

Examples:
- customers.view
- customers.create
- customers.update
- customers.delete
- invoices.send
- inventory.adjust
```

**Usage:**
```php
// Check permission
$user->can('customers.create');

// Check role
$user->hasRole('admin');

// Assign permission
$user->givePermissionTo('customers.create');

// Assign role
$user->assignRole('manager');
```

#### Attribute-Based Access Control (ABAC)

Fine-grained access control based on attributes:

```php
Gate::define('update-customer', function ($user, $customer) {
    // Tenant isolation
    if ($user->tenant_id !== $customer->tenant_id) {
        return false;
    }
    
    // Branch restriction
    if ($user->branch_id && $user->branch_id !== $customer->branch_id) {
        return false;
    }
    
    return $user->can('customers.update');
});
```

## Data Protection

### Encryption

#### 1. Data at Rest

**Database Encryption:**
- Field-level encryption for sensitive data
- Encrypted using AES-256
- Key rotation supported

**Encrypted Fields:**
```php
protected $encrypted = [
    'tax_number',
    'credit_card_number',
    'bank_account_number',
    'social_security_number'
];
```

**File Storage Encryption:**
- S3 server-side encryption (SSE-S3 or SSE-KMS)
- Client-side encryption for highly sensitive files

#### 2. Data in Transit

**TLS/SSL:**
- TLS 1.2 minimum (TLS 1.3 preferred)
- Strong cipher suites only
- HSTS (HTTP Strict Transport Security)
- Certificate pinning for mobile apps

**Nginx SSL Configuration:**
```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers HIGH:!aNULL:!MD5;
ssl_prefer_server_ciphers on;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

#### 3. Password Security

**Password Requirements:**
- Minimum 8 characters
- Mix of uppercase, lowercase, numbers, and special characters
- Common password blacklist
- Password history (prevent reuse of last 5 passwords)
- Password expiration (configurable, default: 90 days)

**Storage:**
- Bcrypt hashing with cost factor 12
- Automatic rehashing on login if cost factor changes

```php
// Password hashing
$password = Hash::make($plaintext, ['rounds' => 12]);

// Verification
Hash::check($plaintext, $hashedPassword);
```

### Data Minimization

Only collect and store necessary data:

- PII (Personally Identifiable Information) limited to business need
- Automatic data purging based on retention policies
- Right to be forgotten (GDPR compliance)

### Data Retention

**Retention Policies:**
- User data: Until account deletion + 30 days
- Transaction data: 7 years (compliance requirement)
- Audit logs: 3 years
- Backups: 30 days rolling

## Network Security

### Firewall Rules

**Inbound Rules:**
```
Allow:
- Port 443 (HTTPS) from anywhere
- Port 22 (SSH) from specific IPs only
- Port 5432 (PostgreSQL) from application servers only
- Port 6379 (Redis) from application servers only

Deny:
- All other inbound traffic
```

**Outbound Rules:**
```
Allow:
- HTTPS (443) to anywhere (for API calls, updates)
- SMTP (587) to mail servers
- DNS (53)

Deny:
- All other outbound traffic
```

### DDoS Protection

- CloudFlare / AWS Shield for DDoS mitigation
- Rate limiting at multiple layers
- Connection limits
- Request size limits

### VPC and Network Segmentation

**Production Architecture:**
```
┌─────────────────────────────────────────────────┐
│              Public Subnet                      │
│  - Load Balancer                                │
│  - NAT Gateway                                  │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│              Private Subnet 1                   │
│  - Application Servers                          │
│  - Queue Workers                                │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│              Private Subnet 2                   │
│  - Database (RDS)                               │
│  - Redis (ElastiCache)                          │
└─────────────────────────────────────────────────┘
```

## Application Security

### Input Validation

**All inputs are validated:**

```php
// Form Request Validation
class CreateCustomerRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0'
        ];
    }
}
```

**Validation Rules:**
- Type checking
- Length limits
- Format validation (email, URL, etc.)
- Business rule validation
- SQL injection prevention (parameterized queries)
- XSS prevention (output escaping)

### Output Encoding

**Automatic Escaping:**
- Blade templates auto-escape output
- Manual escaping when needed: `{{ e($variable) }}`
- JSON response encoding

### CSRF Protection

All state-changing operations require CSRF token:

```html
<form method="POST">
    @csrf
    <!-- form fields -->
</form>
```

API requests use Sanctum tokens (exempt from CSRF).

### SQL Injection Prevention

**Use Eloquent ORM and Query Builder:**

```php
// Safe - parameterized
User::where('email', $email)->first();

// Safe - parameter binding
DB::select('select * from users where email = ?', [$email]);

// Unsafe - never do this
DB::select("select * from users where email = '$email'");
```

### XSS Prevention

**Content Security Policy (CSP):**
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';
```

**Output Escaping:**
```php
// Blade auto-escapes
{{ $userInput }}

// Raw output (use with caution)
{!! $trustedHtml !!}
```

### Clickjacking Protection

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
```

### File Upload Security

**Validation:**
- File type whitelist
- File size limits
- MIME type verification
- Virus scanning (ClamAV)
- Unique filename generation
- Storage outside webroot

```php
$request->validate([
    'file' => 'required|file|mimes:pdf,jpg,png|max:10240'
]);

// Store with unique name
$path = $request->file('file')->store('uploads', 's3');
```

## Multi-Tenancy Security

### Tenant Isolation

**Database Level:**

```php
// Global scope on all tenant models
protected static function bootTenantScoped()
{
    static::addGlobalScope('tenant', function ($builder) {
        if (auth()->check()) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    });
    
    static::creating(function ($model) {
        if (auth()->check() && !$model->tenant_id) {
            $model->tenant_id = auth()->user()->tenant_id;
        }
    });
}
```

**Middleware Protection:**

```php
class TenantAwareMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $user = auth()->user();
        $requestedTenant = $request->header('X-Tenant-ID');
        
        if ($requestedTenant && $requestedTenant != $user->tenant_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        return $next($request);
    }
}
```

### Data Leakage Prevention

**Query Verification:**
- All queries automatically scoped to tenant
- No cross-tenant data access
- Foreign key constraints respect tenant boundaries

**Testing:**
- Automated tests verify tenant isolation
- Penetration testing for cross-tenant access

## Audit & Logging

### Activity Logging

**Spatie Activity Log:**

```php
// Automatic logging
use LogsActivity;

protected static function bootLogsActivity()
{
    static::eventsToBeRecorded = ['created', 'updated', 'deleted'];
}

// Manual logging
activity()
    ->performedOn($customer)
    ->causedBy(auth()->user())
    ->withProperties(['ip' => request()->ip()])
    ->log('Customer updated');
```

**Logged Events:**
- User authentication (login, logout, failed attempts)
- Data modifications (create, update, delete)
- Permission changes
- Configuration changes
- API access
- File access
- Security events

### Immutable Audit Trails

**Append-Only Tables:**
- Stock ledger
- Financial transactions
- Audit log

**Characteristics:**
- No UPDATE or DELETE operations
- Complete history preserved
- Cryptographic hash of previous record (blockchain-like)

```php
Schema::create('audit_log', function (Blueprint $table) {
    $table->id();
    $table->string('event');
    $table->morphs('subject');
    $table->foreignId('user_id')->constrained();
    $table->json('properties');
    $table->ipAddress('ip_address');
    $table->string('user_agent');
    $table->timestamp('created_at');
    // NO updated_at or deleted_at
});
```

### Log Retention

**Retention Periods:**
- Security events: 3 years
- Application logs: 30 days
- Access logs: 90 days
- Compliance logs: 7 years

**Log Storage:**
- Centralized logging (ELK Stack)
- Encrypted at rest
- Access controlled
- Tamper-proof

## Compliance

### GDPR (General Data Protection Regulation)

**Data Subject Rights:**

1. **Right to Access:**
```php
Route::get('/api/v1/me/data', function () {
    return auth()->user()->getAllData();
});
```

2. **Right to Erasure:**
```php
Route::delete('/api/v1/me', function () {
    auth()->user()->anonymize(); // GDPR-compliant deletion
});
```

3. **Right to Portability:**
```php
Route::get('/api/v1/me/export', function () {
    return auth()->user()->exportData(); // JSON format
});
```

**Privacy by Design:**
- Data minimization
- Purpose limitation
- Storage limitation
- Pseudonymization
- Encryption

### SOC 2 Type II

**Security Principles:**
- Security
- Availability
- Processing Integrity
- Confidentiality
- Privacy

**Controls:**
- Access controls
- Encryption
- Monitoring
- Incident response
- Business continuity

### PCI DSS (Payment Card Industry Data Security Standard)

For payment processing:

**Requirements:**
- No storage of cardholder data (use tokenization)
- Strong access controls
- Encrypted transmission
- Regular security testing
- Secure network architecture

**Implementation:**
- Use Stripe/PayPal for card processing
- Only store tokens, never full card numbers
- PCI-compliant infrastructure

### HIPAA (Optional for Healthcare)

If handling healthcare data:

- PHI encryption
- Access controls
- Audit logging
- Business associate agreements
- Breach notification procedures

## Incident Response

### Incident Response Plan

**Phases:**

1. **Preparation:**
   - Incident response team defined
   - Tools and processes in place
   - Regular drills

2. **Detection and Analysis:**
   - Monitoring and alerting
   - Log analysis
   - User reports

3. **Containment:**
   - Isolate affected systems
   - Prevent further damage
   - Preserve evidence

4. **Eradication:**
   - Remove threat
   - Patch vulnerabilities
   - Update security controls

5. **Recovery:**
   - Restore services
   - Verify systems
   - Monitor for reoccurrence

6. **Post-Incident:**
   - Lessons learned
   - Update procedures
   - Improve defenses

### Security Incident Categories

**High Priority:**
- Data breach
- Ransomware
- SQL injection
- Authentication bypass

**Medium Priority:**
- DDoS attack
- Malware infection
- Unauthorized access attempt
- Data integrity issue

**Low Priority:**
- Failed login attempts
- Policy violations
- Suspicious activity

### Breach Notification

**Timeline:**
- Internal notification: Immediate
- User notification: Within 72 hours
- Regulatory notification: As required by law (typically 72 hours)

**Notification Content:**
- Nature of breach
- Data affected
- Actions taken
- User recommendations
- Contact information

## Security Best Practices

### For Developers

1. **Never commit secrets to version control**
   - Use environment variables
   - Use secret management (AWS Secrets Manager, HashiCorp Vault)

2. **Use parameterized queries**
   - Always use Eloquent or Query Builder
   - Never concatenate user input into SQL

3. **Validate all inputs**
   - Server-side validation (never trust client)
   - Use Form Requests
   - Whitelist approach

4. **Escape all outputs**
   - Use Blade templates
   - Be careful with {!! !!}

5. **Keep dependencies updated**
   - Regular `composer update`
   - Monitor security advisories
   - Automated dependency scanning

6. **Use HTTPS everywhere**
   - Force HTTPS in production
   - HSTS headers
   - Secure cookies only

7. **Implement proper error handling**
   - Don't expose stack traces in production
   - Log errors securely
   - Generic error messages to users

8. **Follow principle of least privilege**
   - Minimum necessary permissions
   - Time-limited access
   - Regular access reviews

### For Administrators

1. **Strong password policy**
   - Enforce complexity requirements
   - Password expiration
   - MFA for all admin accounts

2. **Regular security audits**
   - Code reviews
   - Penetration testing
   - Vulnerability scanning

3. **Keep systems updated**
   - Security patches
   - OS updates
   - Application updates

4. **Monitor and alert**
   - Failed login attempts
   - Unusual activity
   - Performance anomalies

5. **Backup and recovery**
   - Regular automated backups
   - Test recovery procedures
   - Off-site backup storage

6. **Incident response drills**
   - Regular tabletop exercises
   - Update procedures
   - Train team members

### For Users

1. **Use strong, unique passwords**
2. **Enable MFA**
3. **Be cautious with emails (phishing)**
4. **Keep software updated**
5. **Report suspicious activity**
6. **Don't share credentials**

## Security Testing

### Automated Security Scanning

**Tools in CI/CD:**
- CodeQL (static analysis)
- Snyk (dependency scanning)
- OWASP ZAP (dynamic analysis)
- npm audit / composer audit

### Manual Security Testing

**Annual Penetration Testing:**
- Black box testing
- White box testing
- API security testing
- Social engineering assessment

## Security Contacts

**Report Security Issues:**
- Email: security@autoerp.com
- PGP Key: Available at https://autoerp.com/.well-known/pgp-key.asc
- Bug Bounty: https://hackerone.com/autoerp

**Response Time:**
- Critical issues: 4 hours
- High priority: 24 hours
- Medium priority: 72 hours
- Low priority: 1 week

---

**Last Updated**: 2026-01-31
**Version**: 1.0.0
