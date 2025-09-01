# Security Testing Documentation

This document outlines the security testing strategy for the Weather Forecaster Agent application.

## Security Testing Areas

### 1. Authentication & Authorization
- [ ] Input validation on login/registration forms
- [ ] Password policy enforcement
- [ ] Session management
- [ ] Access control to protected resources
- [ ] CSRF protection

### 2. API Security
- [ ] Input validation for all API endpoints
- [ ] Rate limiting to prevent abuse
- [ ] Proper error handling without information leakage
- [ ] Authentication for sensitive endpoints
- [ ] API keys management

### 3. Data Protection
- [ ] Secure storage of sensitive data
- [ ] Data encryption in transit (HTTPS)
- [ ] Protection against SQL injection
- [ ] Protection against XSS attacks
- [ ] Content Security Policy implementation

### 4. Dependency Security
- [ ] Scanning for vulnerable dependencies
- [ ] Regular updates of libraries and frameworks
- [ ] Removal of unused dependencies

### 5. Configuration & Environment
- [ ] Secure environment configurations
- [ ] Protection of environment variables
- [ ] Proper error handling in production
- [ ] Secure HTTP headers
- [ ] Server hardening

## Security Testing Tools

### 1. Static Application Security Testing (SAST)
- PHP_CodeSniffer with security rules
- ESLint with security plugins
- SonarQube for code quality and security

### 2. Dynamic Application Security Testing (DAST)
- OWASP ZAP (Zed Attack Proxy)
- Burp Suite

### 3. Dependency Scanning
- Composer audit
- npm audit
- GitHub Dependabot

### 4. Security Headers Check
- [SecurityHeaders.com](https://securityheaders.com)
- Mozilla Observatory

## Security Testing Scripts

### 1. CSRF Protection Test

```php
<?php

namespace Tests\Security;

use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    /**
     * Test that CSRF protection is working.
     */
    public function test_csrf_protection(): void
    {
        // Create a request without CSRF token
        $response = $this->post('/api/weather/query', [
            'query' => 'Test query',
        ]);
        
        // Should be rejected with 419 status (CSRF token mismatch)
        $response->assertStatus(419);
    }
}
```

### 2. XSS Protection Test

```php
<?php

namespace Tests\Security;

use Tests\TestCase;

class XssProtectionTest extends TestCase
{
    /**
     * Test that XSS protection is working.
     */
    public function test_xss_protection(): void
    {
        // Try to inject a script
        $xssPayload = '<script>alert("XSS")</script>';
        
        $response = $this->withSession(['_token' => csrf_token()])
            ->postJson('/api/weather/query', [
                'query' => $xssPayload,
                'session_id' => 'test_session',
            ]);
        
        // Check that the response doesn't contain the unescaped script
        $response->assertDontSee($xssPayload, false);
        $response->assertStatus(200);
        
        // Check the message doesn't contain the unescaped script
        $this->assertStringNotContainsString($xssPayload, $response->json('message'));
    }
}
```

### 3. SQL Injection Protection Test

```php
<?php

namespace Tests\Security;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlInjectionTest extends TestCase
{
    /**
     * Test that SQL injection protection is working.
     */
    public function test_sql_injection_protection(): void
    {
        // SQL injection payloads to test
        $sqlInjectionPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "' UNION SELECT * FROM users --",
        ];
        
        foreach ($sqlInjectionPayloads as $payload) {
            // Count users before the request
            $usersBefore = DB::table('users')->count();
            
            // Make the request with the SQL injection payload
            $response = $this->withSession(['_token' => csrf_token()])
                ->postJson('/api/weather/query', [
                    'query' => "Weather in {$payload}",
                    'session_id' => 'test_session',
                ]);
            
            // Count users after the request
            $usersAfter = DB::table('users')->count();
            
            // Ensure the database wasn't modified
            $this->assertEquals($usersBefore, $usersAfter);
            
            // Request should still succeed, but be safe
            $response->assertStatus(200);
        }
    }
}
```

## Security Headers Configuration

Implement secure HTTP headers in your web server configuration:

### Nginx Example
```nginx
server {
    # ...
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self' api.open-meteo.com geocoding-api.open-meteo.com api.bigdatacloud.net; frame-ancestors 'none';";
    add_header Permissions-Policy "geolocation=(self)";
    
    # ...
}
```

### Apache Example
```apache
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self' api.open-meteo.com geocoding-api.open-meteo.com api.bigdatacloud.net; frame-ancestors 'none';"
    Header set Permissions-Policy "geolocation=(self)"
</IfModule>
```

## Laravel Security Best Practices

1. **Use the latest framework version**
   - Keep Laravel updated to the latest version

2. **Protect against CSRF**
   - Use Laravel's built-in CSRF protection:
   ```php
   @csrf
   ```

3. **Validate all input**
   - Use Laravel's validation features:
   ```php
   $request->validate([
       'query' => 'required|string|max:500',
       'session_id' => 'nullable|string|max:100',
   ]);
   ```

4. **Use prepared statements**
   - Laravel's Eloquent ORM and Query Builder use prepared statements by default

5. **Secure API endpoints**
   - Use throttling to prevent abuse:
   ```php
   Route::middleware(['throttle:60,1'])->group(function () {
       Route::post('/api/weather/query', [WeatherAgentController::class, 'query']);
   });
   ```

6. **Use HTTPS everywhere**
   - Force HTTPS in production:
   ```php
   if (app()->environment('production')) {
       URL::forceScheme('https');
   }
   ```

## Vue.js Security Best Practices

1. **Prevent XSS**
   - Vue automatically escapes content, but be careful with `v-html`

2. **Sanitize user input**
   - Always validate and sanitize data on the server-side, not just the client-side

3. **Use Vue's built-in protections**
   - Don't use `v-html` with untrusted content

4. **Secure local storage usage**
   - Don't store sensitive information in localStorage

## Continuous Security Testing

Integrate security testing into the CI/CD pipeline:

```yaml
# Example GitHub Actions workflow
name: Security Scan

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]
  schedule:
    - cron: '0 0 * * 0'  # Weekly scan

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Run Composer Audit
        run: composer audit
        
      - name: Run npm Audit
        run: npm audit
        
      - name: Run OWASP ZAP Scan
        uses: zaproxy/action-baseline@v0.6.1
        with:
          target: 'https://staging-app-url.com'
```

## Security Incident Response Plan

1. **Preparation**
   - Maintain an up-to-date inventory of systems and data
   - Establish roles and responsibilities
   - Create communication templates

2. **Detection & Analysis**
   - Monitor logs and alerts
   - Document all findings
   - Determine the scope of the incident

3. **Containment**
   - Isolate affected systems
   - Take temporary mitigation measures
   - Preserve evidence

4. **Eradication**
   - Remove malicious code or unauthorized access
   - Fix vulnerabilities
   - Scan for additional compromises

5. **Recovery**
   - Restore systems from clean backups
   - Monitor for recurrence
   - Implement additional security controls

6. **Post-Incident**
   - Document lessons learned
   - Update security measures
   - Improve incident response process

## Security Testing Checklist

### Before Each Release:
1. [ ] Run dependency vulnerability scans
2. [ ] Run automated security tests
3. [ ] Check security headers
4. [ ] Validate input handling for all new features
5. [ ] Review API endpoint security
6. [ ] Check error handling and logging
7. [ ] Verify HTTPS configuration
8. [ ] Test authentication and authorization

### Quarterly:
1. [ ] Conduct a full DAST scan
2. [ ] Perform a manual security review
3. [ ] Review security logs for suspicious activity
4. [ ] Update security documentation
5. [ ] Run a penetration test (if resources allow)

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [Vue.js Security](https://vuejs.org/guide/best-practices/security.html)
- [Mozilla Web Security Guidelines](https://infosec.mozilla.org/guidelines/web_security)
