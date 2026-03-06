Here is the polished, GitHub-ready **`SECURITY.md`**.

````md
# SECURITY.md

## Project Security Overview

Quizmania is a legacy **Core PHP + MySQL** web application hardened to a **moderate, internship-ready security baseline** while preserving the existing architecture.

This file documents:
- the security model used during hardening
- the key protections implemented
- known limitations and future improvements
- how to report security issues responsibly

---

## Threat Model

### In-scope attackers
1. Unauthenticated internet users attempting direct endpoint access
2. CSRF attacks targeting logged-in users/admins
3. SQL injection attempts through request inputs
4. Privilege escalation (horizontal/vertical) via weak authorization checks
5. Stored XSS through database-rendered fields (names, content, chat)
6. Session abuse, including fixation and hijacking attempts
7. Abuse of AJAX endpoints (chat moderation/tools, admin actions)

### Out of scope
- Server compromise / root access
- Direct database server access
- Network-level MITM attacks (assuming HTTPS is configured correctly)
- Infrastructure-layer DDoS mitigation

---

## Security Controls Implemented

### 1. CSRF protection (forms + AJAX)
- CSRF module: `src/security/csrf.php`
- Forms include `csrf_input()`
- POST handlers enforce `csrf_verify()`
- AJAX endpoints include CSRF tokens in payload and verify server-side

### 2. POST-only state changes
All state-changing actions (`INSERT`, `UPDATE`, `DELETE`, status changes, moderation, assignment, cancellation, reset) are enforced as:
- POST-only
- CSRF-verified
- redirected safely using `header("Location: ..."); exit;`
- JSON response + `exit` for AJAX endpoints

### 3. SQL injection mitigation
Critical handlers were migrated to prepared statements, especially for:
- authentication and account flows
- admin actions
- purchases, assignments, and cancellations
- moderation actions (MCQ verification, report resolve/delete)
- chat actions

### 4. Authentication and session defenses
- Passwords stored using `password_hash()` and verified using `password_verify()`
- Session cookie hardening:
  - strict mode
  - only cookies
  - `httponly`
  - `samesite=Lax`
- Session token validation + single-device session enforcement
- Login throttling using the `login_attempts` table
- Session regeneration on login

### 5. Output escaping (XSS mitigation)
Pages rendering user-controlled or DB-controlled data use:
- `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`

Additional sanitization is applied in high-risk displays where needed, such as stripping tags before rendering question content in admin reports.

### 6. Direct access protection for internal files
`.htaccess` rules block direct access to internal `/src` PHP files while allowing API endpoints:
- allow: `src/api/*.php`
- block: `src/db`, `src/inc`, and other internal `/src` PHP paths

### 7. Attack surface reduction
Unused modules were disabled with **HTTP 410 Gone** to reduce reachable legacy attack surface.

---

## Security Rules for Contributors

### Mandatory pattern for DB-write handlers
Every DB-write endpoint must follow this order:

1. POST-only
2. `csrf_verify()`
3. authentication check (logged-in user)
4. authorization check (role / privilege / ownership)
5. server-side validation
6. prepared statement
7. redirect + `exit` (or JSON + `exit`)

### UI rule for destructive actions
Destructive actions must be triggered by:

```php
<form method="POST">
  <?= csrf_input(); ?>
  <button type="submit">Action</button>
</form>
````

### Never

* destructive actions via GET
* JS redirects (`window.location.href`) for state-changing actions
* `PHP_SELF` as a form action

---

## Known Limitations

This project does **not** currently implement:

* Content Security Policy (CSP)
* global rate limiting across all endpoints
* centralized validation middleware
* automated security test suite (SAST/DAST in CI)
* a full global security-header baseline

---

## Future Improvements Roadmap

Recommended next steps:

1. Add global security headers (`nosniff`, frame protection, referrer policy; HSTS after full HTTPS confirmation)
2. Add CSP in Report-Only mode, fix violations, then enforce
3. Centralize authorization and validation helpers to reduce inconsistency risk
4. Add lightweight scanning (OWASP ZAP baseline) and document findings
5. Add security logging around admin actions and repeated login failures

---

## Reporting a Vulnerability

If you discover a security vulnerability:

1. Do **not** open a public issue
2. Report privately with reproduction steps, affected endpoint(s), and impact
3. Include:

   * request sample (method + params)
   * expected vs actual behavior
   * severity estimate

---

## Security Contact

For responsible disclosure, contact the project maintainer via the repository contact method or listed email, if available.
