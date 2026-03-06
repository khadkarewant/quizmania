# Quizmania Security Hardening Notes

This document records the security hardening applied to Quizmania, a live user-facing Core PHP + MySQL application, to reach a moderate, portfolio-ready security baseline while preserving the existing architecture.

---

## Scope and Principles

### Stack
- Core PHP
- MySQL
- No framework

### Goal
Reduce common web attack surfaces while keeping scope controlled and preserving the existing architecture.

### Hardening Focus Areas
- CSRF
- SQL Injection
- Authentication bypass
- Session hijacking
- Privilege escalation / IDOR
- Unsafe redirects
- Direct access to internal files
- AJAX endpoint safety (chat + admin tools)

### Rules Adopted
Any DB-write action (`INSERT`, `UPDATE`, `DELETE`, status change, verification, assignment, cancellation, moderation action) must be:

- POST-only
- CSRF-protected with `csrf_verify()`
- Implemented with prepared statements
- Authorization-checked
- Server-side validated
- Redirected with `header("Location: ..."); exit;`

For AJAX endpoints:
- JSON response
- `exit`

Destructive UI actions must use:

```php
<form method="POST">
  <?= csrf_input(); ?>
</form>
````

### Never

* Trigger destructive actions via GET
* Use `window.location.href` for state-changing actions
* Use JS redirects for destructive actions
* Use `PHP_SELF` as a form action

---

## Threat Model

### Assumed attacker capabilities

1. Unauthenticated internet users attempting direct endpoint access
2. CSRF attacks targeting logged-in users/admins
3. SQL injection attempts through request inputs
4. Session theft attempts
5. Privilege escalation through improperly protected endpoints
6. Direct URL access to internal PHP files
7. Endpoint abuse through AJAX calls (chat send/delete, admin actions)

### Out of scope

* Infrastructure attacks
* Server compromise
* Direct database server access

---

## CSRF System

CSRF protection is provided by:

* `src/security/csrf.php`

### Usage enforced

* Handlers: `csrf_verify();`
* Forms: `<?= csrf_input(); ?>`

### Important implementation rule

During hardening, one recurring issue was duplicate session/bootstrap loading.

Rules adopted:

* Avoid including `public_bootstrap.php` in files that already include `src/db/session.php`
* Use `require_once("src/security/csrf.php");` to prevent function redeclare fatals

---

## Session Security Improvements

Session configuration hardened in `src/db/session.php`:

* `session.use_strict_mode = 1`
* `session.use_only_cookies = 1`
* `session.cookie_httponly = true`
* `session.cookie_samesite = Lax`

### Additional protections

* Session token stored in database
* Single-device login enforcement
* Token validated on every request
* Invalid session triggers forced logout

---

## Tier-0: High-Risk Surface Closure (GET → POST + CSRF)

High-risk endpoints previously driven by GET were converted to **POST + CSRF**.

### Converted / secured endpoints

* `delete-question-pattern.php`
* `delete-question-topic.php`
* `cancel-purchase-product.php`
* `course-status-change.php`
* `product-status-change.php`
* `assign-product.php`
* `question_update-query.php`
* `mark-read-notification.php`

### Each endpoint now

* Rejects GET
* Requires POST
* Verifies CSRF
* Redirects safely, or returns JSON + `exit`

### Standard pattern

```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ...");
    exit;
}
csrf_verify();
```

---

## Caller Pages Updated (UI)

Destructive actions were converted from JS redirects to secure POST forms.

### Updated pages

* `product-details.php`
* `question-pattern-details.php`
* `purchase-history.php`
* `courses.php`
* `assign-product.php`
* `updatable-mcqs.php`
* `notification.php`
* `user-details.php`

### Examples

* MCQ “need upgrade” Yes/No now uses POST forms to `question_update-query.php`
* Notifications “Mark read” now uses a POST form to `mark-read-notification.php`
* Password reset in `user-details.php` now uses POST + CSRF

---

## Infrastructure Hardening (`.htaccess`)

Internal PHP files were protected from direct web access.

### Original problem

Blocking all `/src` PHP also blocked API endpoints such as:

* `src/api/data-check-api.php`

### Final rules

```apache
RewriteRule ^src/api/.*\.php$ - [L]
RewriteRule ^src/(?!api/).*\.php$ - [F,L]
```

### Result

Allowed:

* `src/api/*.php`

Blocked:

* `src/db`
* `src/inc`
* Other internal `/src` PHP files

---

## Bug Fixes Discovered During Hardening

### Purchase cancel status mismatch

Database enum:

* `enum('active','inactive','expired')`

Original code used:

* `status = 'cancelled'`

Fix:

* `status = 'inactive'`

### Redirect safety

Removed JavaScript redirects such as:

```php
echo "<script>window.location.href='page.php'</script>";
```

Replaced with:

```php
header("Location: page.php");
exit;
```

### Output buffering removal

Removed `ob_start();` where it was being used to mask redirect/header problems.

Reason:

* It hid output-before-header bugs
* It encouraged unsafe redirect flow

---

## Tier-1 SQL Injection Hardening

Critical handlers were upgraded to prepared statements.

### Hardened endpoints

* `product-status-change.php`
* `course-status-change.php`
* `verify-mcq.php`
* `mcq-status-change.php`
* `delete-mcq.php`
* `assign-product.php`
* `cancel-purchase-product.php`
* `reset-user-password.php`
* `question_update-query.php`
* `mark-read-notification.php`
* `admin-chat-reports.php`
* `admin-mcq-reports.php`

### Example pattern

```php
$stmt = $conn->prepare("UPDATE table SET col=? WHERE id=?");
$stmt->bind_param("si", $value, $id);
$stmt->execute();
$stmt->close();
```

### Additional protections

* Integer casting
* Allowlists for action/status values
* Basic transaction safety where appropriate

---

## Authentication Hardening

### Files hardened

* `login.php`
* `signup.php`
* `change-password.php`

### Improvements

* `password_hash()` storage
* `password_verify()` validation
* Prepared statements
* CSRF protection
* Login throttling
* Session regeneration on login

### Throttling implemented using

* `login_attempts` table

---

## PIN System Security Upgrade

### Legacy PIN storage

* `md5(pin)`

### Replaced with

* `password_hash(pin)`

### Files secured

* `set-pin.php`
* `change-pin.php`

### Design decision

Users can change PIN by confirming their account password instead of requiring the old PIN.

---

## Admin Handler Hardening

### `add-user.php`

Applied:

* CSRF protection
* Prepared statements
* Username/email/phone uniqueness checks
* Password hashing
* Server-side validation, including DOB validation
* Admin authorization check
* Safe redirects
* Duplicate key handling, with DB UNIQUE indexes confirmed

### `add-product.php`

Applied:

* CSRF protection
* Prepared statements
* Secure course lookup
* Server-side validation
* Authorization checks
* Safe redirects
* No raw SQL errors exposed to users
* Corrected `bind_param` type string

### `reset-user-password.php`

Converted from GET to POST + CSRF:

* Prepared update for password reset and forced logout
* Safe redirects
* Optional self-reset blocking

---

## Profile Update Handlers Hardening

### Hardened endpoints

* `update-personal-info.php`
* `update-contact.php`
* `update-address.php`

### Applied

* POST-only
* CSRF verification
* Prepared statements
* Validation
* Safe redirects + `exit`
* Removed `PHP_SELF` usage in form actions
* Reduced XSS exposure by escaping form values

---

## XSS Output Escaping Sweep

Key pages that render user-controlled or DB-controlled values were updated to use:

```php
htmlspecialchars(..., ENT_QUOTES, 'UTF-8')
```

### Updated pages

* `profile.php`
* `users.php`
* `user-details.php`

### Additional display hardening

* `admin-mcq-reports.php`: question output uses `strip_tags()` + escaping to prevent stored HTML such as `<p>...</p>` from rendering

---

## Chat System Hardening

### Hardened files

* `chat-actions.php`
* `discussion.php`

### `chat-actions.php` improvements

* CSRF required for POST send/delete
* Raw SQL converted to prepared statements for:

  * user lookup
  * student access check
  * message ownership lookup
  * fetch messages query
  * delete UPDATE query
* Delete permissions unified using privilege flags:

  * `$delete_any_chat`
  * `$delete_own_chat`
* Fixed `reply_to` NULL handling
* Added message length cap (1000 chars)
* Used `require_once` for CSRF include

### `discussion.php` improvements

* Added `<?= csrf_input(); ?>` inside chat form
* Delete AJAX updated to include CSRF token
* Added JSON fail handler for debugging

### Debugging outcome

* Delete initially failed with `403 CSRF token missing`
* Fixed by sending the CSRF token from the chat form into the AJAX delete request

---

## Admin Reports Hardening

### `admin-chat-reports.php`

* Resolve/delete actions moved to prepared statements
* CSRF verification required for AJAX actions
* CSRF token injected into AJAX payload
* JSON responses with safe exits

### `admin-mcq-reports.php`

* Same hardening as above
* Question output sanitized for display with `strip_tags()` + escaping

---

## `learn.php` Reset Product Hardening

Practice product reset endpoint was hardened.

### Applied

* AJAX reset now requires CSRF verification
* Topic lookup for reset uses a prepared statement
* Response returned as JSON
* Display bug fixed: `$product` undefined warning replaced with `$products[0]['course_name'] ?? ''`

### Note

The delete query still uses an `IN (...)` list built from topic IDs sourced from the database. This is acceptable for the current baseline, but a future improvement would convert it to a fully prepared dynamic placeholder query and add explicit ownership/access checks.

---

## Unused Module Deactivation

Unused endpoints were disabled with:

```php
http_response_code(410);
exit('Gone');
```

### Disabled modules

* `a-assign-product.php`
* `admin-allowed-user.php`
* `block-user-chat.php`
* `unblock.php`

Associated UI links were removed or commented where relevant.

---

## Verification Checklist

### UI testing confirmed

* Product live/draft toggle works
* Course live/draft toggle works
* MCQ moderation works:

  * verify
  * status change
  * delete
* Purchase cancellation sets status to `inactive`
* Product assignment works; duplicate transaction numbers are rejected
* Chat fetch/send/delete works with CSRF
* Admin reports resolve/delete works via CSRF-protected AJAX
* Notifications mark-read works via POST + CSRF
* MCQ “need upgrade” Yes/No works via POST + CSRF

### Network inspection confirmed

* POST is used for state-changing actions
* CSRF tokens are included on state-changing requests, including AJAX
* Remaining GET endpoints are view-only or navigation-only

---

## Remaining Hardening Work (Future)

This project is paused for now. Improvements can resume later.

### High-value next items

1. Convert remaining raw `DELETE` / `UPDATE` queries to prepared statements where any are still present
2. Add ownership/access checks on `learn.php` reset product action
3. Add global security headers:

   * `X-Content-Type-Options: nosniff`
   * `X-Frame-Options: SAMEORIGIN`
   * `Referrer-Policy: strict-origin-when-cross-origin`
4. Optional polish:

   * Logout via POST + CSRF
   * Centralized authorization helper functions
   * CSP in Report-Only, then enforce
5. Run a lightweight scan:

   * OWASP ZAP baseline
6. Documentation packaging:

   * `SECURITY.md`
   * `CHANGELOG.md`

---

## Known Limitations

This hardening does not currently implement:

* Full Content Security Policy (CSP)
* Global rate limiting across all endpoints
* Automated security test suite
* Centralized validation middleware
* Framework-level protections

---

## Security Controls Summary

### Implemented protections

* CSRF protection for forms and AJAX
* POST-only destructive endpoints
* Prepared statements on critical handlers
* Login throttling
* Secure password storage
* Secure PIN hashing
* Session hardening + token validation
* Role/privilege checks via `privileges.php`
* Internal path protection via `.htaccess`
* Safe redirect patterns
* Chat endpoint hardening
* Admin moderation tools hardened
* `learn.php` reset action CSRF hardened

---

## Conclusion

The application is now significantly more secure than the original legacy version and is suitable as a **portfolio-level backend security hardening project**.

```
```
