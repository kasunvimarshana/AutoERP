# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

The AutoERP team takes security bugs seriously. We appreciate your efforts to responsibly disclose your findings.

### How to Report

**DO NOT** create a public GitHub issue for security vulnerabilities.

Instead, please report security vulnerabilities to:

📧 **Email**: security@autoerp.com

Include the following information:
- Type of vulnerability
- Full paths of source file(s) related to the vulnerability
- Location of the affected source code (tag/branch/commit or direct URL)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the vulnerability
- Any potential mitigations you've identified

### What to Expect

1. **Acknowledgment**: We'll acknowledge your email within 48 hours
2. **Investigation**: We'll investigate and validate the issue
3. **Updates**: We'll keep you informed of progress
4. **Fix**: We'll develop and test a fix
5. **Release**: We'll release a security patch
6. **Disclosure**: We'll publicly disclose the vulnerability after the patch is released

### Timeline

- **Initial Response**: Within 48 hours
- **Investigation**: 1-5 business days
- **Fix Development**: Varies based on complexity
- **Patch Release**: As soon as possible, typically within 7-30 days

## Security Best Practices

### For Users

#### Authentication
- Use strong, unique passwords (minimum 12 characters)
- Enable Two-Factor Authentication (2FA/MFA)
- Regularly rotate API tokens
- Never share authentication credentials
- Use secure password managers

#### Data Protection
- Enable encryption at rest (database encryption)
- Use HTTPS/TLS for all connections
- Regularly backup your data
- Implement IP whitelisting where possible
- Monitor access logs for suspicious activity

#### Access Control
- Follow the principle of least privilege
- Regularly review user permissions
- Disable inactive user accounts
- Use role-based access control (RBAC)
- Audit permission changes

#### Environment
- Keep your installation up to date
- Monitor security advisories
- Use environment variables for sensitive data
- Never commit credentials to version control
- Regularly rotate secrets and keys

### For Developers

#### Code Security

##### Input Validation
```php
// Always validate user input
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'name' => 'required|string|max:255',
]);

// Use parameterized queries (Eloquent handles this automatically)
Customer::where('email', $email)->first(); // ✅ Safe
DB::raw("SELECT * FROM customers WHERE email = '$email'"); // ❌ Dangerous
```

##### SQL Injection Prevention
```php
// ✅ Safe - Use Eloquent ORM
$user = User::where('email', $email)->first();

// ✅ Safe - Use parameter binding
$users = DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ❌ Dangerous - Direct concatenation
$users = DB::select("SELECT * FROM users WHERE email = '$email'");
```

##### XSS Prevention
```php
// Laravel automatically escapes output in Blade
{{ $user->name }} // ✅ Safe

// Unescaped output - use with caution
{!! $html !!} // Only for trusted content

// In JavaScript/TypeScript
const element = document.createElement('div');
element.textContent = userInput; // ✅ Safe
element.innerHTML = userInput; // ❌ Dangerous
```

##### CSRF Protection
```php
// Laravel automatically includes CSRF protection
// In forms, use @csrf directive
<form method="POST">
    @csrf
    <!-- form fields -->
</form>

// In AJAX requests, include CSRF token
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
```

##### Authentication & Authorization
```php
// Always authenticate API requests
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});

// Check permissions before actions
if ($user->can('create-customer')) {
    // Allow action
}

// Use policies for complex authorization
$this->authorize('update', $customer);
```

##### Sensitive Data
```php
// Hide sensitive attributes in model
class User extends Model
{
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'password' => 'hashed',
    ];
}

// Encrypt sensitive data
use Illuminate\Support\Facades\Crypt;

$encrypted = Crypt::encryptString($sensitive);
$decrypted = Crypt::decryptString($encrypted);
```

#### Dependencies

##### Keep Dependencies Updated
```bash
# Check for outdated packages
composer outdated
npm outdated

# Update dependencies
composer update
npm update

# Audit for vulnerabilities
composer audit
npm audit
```

##### Review New Dependencies
- Check package popularity and maintenance
- Review package source code for suspicious activity
- Use only trusted sources (Packagist, npm)
- Monitor security advisories

#### Environment Configuration

##### Environment Variables
```bash
# .env.example - Template without secrets
APP_NAME=AutoERP
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Never commit actual .env file
# Add to .gitignore
.env
.env.local
.env.*.local
```

##### Production Settings
```bash
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error
DB_CONNECTION=pgsql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
```

#### API Security

##### Rate Limiting
```php
// In routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Rate limited to 60 requests per minute
});
```

##### API Token Security
```php
// Token expiration
config(['sanctum.expiration' => 60]); // 60 minutes

// Ability-based tokens
$token = $user->createToken('token-name', ['customer:create', 'customer:read']);

// Revoke tokens
$user->tokens()->delete(); // Revoke all
$user->currentAccessToken()->delete(); // Revoke current
```

##### CORS Configuration
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_origins' => ['https://your-domain.com'],
    'allowed_headers' => ['*'],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

#### Testing Security

```php
// Test authentication
it('requires authentication', function () {
    $response = $this->getJson('/api/v1/customers');
    $response->assertStatus(401);
});

// Test authorization
it('requires permission', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/customers', $data);
    
    $response->assertStatus(403);
});

// Test input validation
it('validates required fields', function () {
    $response = $this->postJson('/api/v1/customers', []);
    $response->assertStatus(422);
});
```

## Common Vulnerabilities

### 1. SQL Injection
**Risk**: Highest  
**Mitigation**: Use Eloquent ORM or parameterized queries

### 2. Cross-Site Scripting (XSS)
**Risk**: High  
**Mitigation**: Escape all user output, use Content Security Policy

### 3. Cross-Site Request Forgery (CSRF)
**Risk**: High  
**Mitigation**: Use Laravel's CSRF protection, validate tokens

### 4. Authentication Bypass
**Risk**: Critical  
**Mitigation**: Proper authentication middleware, secure session handling

### 5. Insecure Direct Object References
**Risk**: High  
**Mitigation**: Authorization checks, policy classes

### 6. Security Misconfiguration
**Risk**: Medium  
**Mitigation**: Secure defaults, regular audits

### 7. Sensitive Data Exposure
**Risk**: High  
**Mitigation**: Encryption, secure transmission, proper access controls

### 8. Insufficient Logging
**Risk**: Medium  
**Mitigation**: Comprehensive logging, log monitoring

### 9. Using Components with Known Vulnerabilities
**Risk**: High  
**Mitigation**: Regular updates, dependency scanning

### 10. Insecure Deserialization
**Risk**: High  
**Mitigation**: Validate serialized data, use secure formats (JSON)

## Security Checklist

### Pre-Deployment

- [ ] All dependencies up to date
- [ ] Security audit completed
- [ ] Penetration testing performed
- [ ] Environment variables configured
- [ ] Debug mode disabled
- [ ] Error reporting configured
- [ ] HTTPS enabled
- [ ] CORS properly configured
- [ ] Rate limiting implemented
- [ ] Input validation in place
- [ ] Output escaping verified
- [ ] Authentication tested
- [ ] Authorization tested
- [ ] SQL injection testing passed
- [ ] XSS testing passed
- [ ] CSRF protection enabled
- [ ] Sensitive data encrypted
- [ ] Logging configured
- [ ] Monitoring set up
- [ ] Backup system tested

### Post-Deployment

- [ ] Monitor logs regularly
- [ ] Review access logs
- [ ] Check for failed login attempts
- [ ] Monitor API usage
- [ ] Regular security audits
- [ ] Update dependencies
- [ ] Review user permissions
- [ ] Test disaster recovery
- [ ] Review and rotate secrets
- [ ] Security awareness training

## Compliance

### GDPR Compliance

AutoERP includes features to support GDPR compliance:

- **Data Export**: Users can export their data
- **Data Deletion**: Right to be forgotten
- **Consent Management**: Track user consents
- **Data Processing Records**: Audit trails
- **Data Protection**: Encryption and access controls

### SOC 2 Compliance

Features supporting SOC 2 compliance:

- **Security**: Role-based access, encryption, audit logs
- **Availability**: Monitoring, backups, disaster recovery
- **Processing Integrity**: Data validation, error handling
- **Confidentiality**: Data encryption, access controls
- **Privacy**: Data protection, consent management

## Security Resources

### Tools

- **OWASP ZAP**: Web application security scanner
- **Burp Suite**: Web vulnerability scanner
- **PHPStan**: PHP static analysis
- **SonarQube**: Code quality and security
- **Snyk**: Dependency vulnerability scanner
- **Dependabot**: Automated dependency updates

### References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [OWASP API Security Top 10](https://owasp.org/www-project-api-security/)
- [CWE Top 25](https://cwe.mitre.org/top25/)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)

## Disclosure Policy

### Responsible Disclosure

We follow responsible disclosure principles:

1. **Private Disclosure**: Report to us privately first
2. **Investigation Period**: Allow time for investigation and fix
3. **Coordinated Disclosure**: Public disclosure after patch release
4. **Credit**: Security researchers will be credited (with permission)

### Public Disclosure

After a security fix is released:

- **Security Advisory**: Published on GitHub
- **Release Notes**: Included in release notes
- **CVE**: Assigned if applicable
- **Credit**: Security researcher credited

## Contact

For security concerns:
- **Email**: security@autoerp.com
- **PGP Key**: Available on request
- **Response Time**: Within 48 hours

For general inquiries:
- **Email**: support@autoerp.com
- **GitHub Issues**: For non-security bugs
- **GitHub Discussions**: For questions

---

**Last Updated**: 2026-02-01  
**Version**: 1.0.0