# CHANGELOG

All notable changes to Quizmania security hardening are documented here.

---

## [Unreleased] — Security Hardening Phase (Core PHP + MySQL)

### Added
- CSRF protection system (`src/security/csrf.php`) applied across forms and AJAX endpoints:
  - `csrf_input()` in forms
  - `csrf_verify()` in POST handlers
- Login throttling backed by the `login_attempts` table
- Session hardening, token validation, and single-device login enforcement in `src/db/session.php`

### Changed

#### Tier-0: GET state changes removed (migrated to POST + CSRF)
Converted destructive or state-changing operations from GET to POST + CSRF with safe redirect flow:

- `delete-question-pattern.php`
- `delete-question-topic.php`
- `cancel-purchase-product.php`
- `course-status-change.php`
- `product-status-change.php`
- `assign-product.php`
- `reset-user-password.php`  
  - caller updated in `user-details.php`
- `question_update-query.php`  
  - MCQ “need upgrade” Yes/No flow
- `mark-read-notification.php`  
  - notifications mark-read flow

#### SQL injection hardening (prepared statements)
Replaced critical raw queries with prepared statements in high-risk handlers and moderation tools, including:

- `product-status-change.php`
- `course-status-change.php`
- `verify-mcq.php`
- `mcq-status-change.php`
- `delete-mcq.php`
- `assign-product.php`
- `cancel-purchase-product.php`
- `reset-user-password.php`
- `question_update-query.php`
- `mark-read-notification.php`
- `admin-chat-reports.php`
  - resolve/delete actions
- `admin-mcq-reports.php`
  - resolve/delete actions
- `learn.php`
  - `product_topics` lookup for reset flow

#### Authentication and account security
Hardened authentication and account-related flows:

- `login.php`
  - prepared statements
  - CSRF protection
  - throttling
  - session regeneration on login
- `signup.php`
  - prepared statements
  - CSRF protection
  - validation
- `change-password.php`
  - prepared statements
  - CSRF protection
  - safe redirects

PIN system upgraded:
- `set-pin.php`
- `change-pin.php`

Storage/verification change:
- `md5` → `password_hash` / `password_verify`

#### Profile update hardening
Hardened user profile update handlers:

- `update-personal-info.php`
- `update-contact.php`
- `update-address.php`

Applied:
- POST-only flow
- CSRF verification
- prepared statements
- validation
- safe redirects
- removal of `PHP_SELF`

#### Output escaping (XSS mitigation)
Added output escaping on key pages rendering user-controlled or DB-controlled data:

- `profile.php`
- `users.php`
- `user-details.php`

Additional display sanitization:
- `admin-mcq-reports.php`
  - strips tags from question output before escaping

#### Chat system hardening (AJAX safety)
Hardened:
- `chat-actions.php`
- `discussion.php`

Applied:
- prepared statements
- CSRF verification
- permission checks
- message length cap
- NULL-safe `reply_to` handling
- AJAX delete request updated to include CSRF
- JSON fail/debug handling added

Resolved bug:
- Delete initially failed with `403 CSRF token missing`
- Fixed by sending the CSRF token with the AJAX delete request

#### Admin moderation tools
Hardened admin report tooling:

- `admin-chat-reports.php`
  - CSRF-protected AJAX resolve/delete
  - prepared statements
  - JSON response flow
- `admin-mcq-reports.php`
  - same protections
  - safer question display

#### `learn.php` reset action
Hardened the “Reset Product” AJAX action:

- CSRF verified on reset
- CSRF token sent in AJAX payload
- `product_topics` lookup moved to prepared statement
- JSON response content-type set

Resolved rendering bug:
- `$product` undefined
- Replaced with `$products[0]['course_name'] ?? ''`

### Fixed
- Purchase cancellation status mismatch:
  - DB enum: `active / inactive / expired`
  - code changed from `cancelled` → `inactive`
- Redirect safety improvements:
  - removed JS redirects
  - replaced with `header("Location: ..."); exit;`
- Output buffering misuse removed:
  - removed `ob_start()` where it masked header issues
- Notification mark-read privilege check fixed:
  - corrected handler permission flag mismatch so mark-read works on live

### Security

#### `.htaccess` internal file access protection
Allowed:
- `src/api/*.php`

Blocked:
- `src/db`
- `src/inc`
- other internal `/src` PHP files from direct access

Rules applied:

```apache id="4z8db8"
RewriteRule ^src/api/.*\.php$ - [L]
RewriteRule ^src/(?!api/).*\.php$ - [F,L]
````

#### Disabled unused modules (attack surface reduction)

Permanently disabled endpoints:

* `a-assign-product.php`
* `admin-allowed-user.php`
* `block-user-chat.php`
* `unblock.php`

Return behavior:

* `http_response_code(410); exit('Gone');`

Related UI entry points were removed or commented where applicable.

---

## Notes

* This changelog reflects a focused security hardening sprint aimed at reducing common web vulnerabilities such as CSRF, SQL injection, unsafe GET actions, and XSS while preserving a legacy Core PHP architecture.
* Future improvements such as CSP, global rate limiting, centralized middleware, and automated security scanning are intentionally deferred.
