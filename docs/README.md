````md
# Quizmania

Quizmania is a **Core PHP + MySQL** learning platform focused on MCQ-based preparation, practice, and product-driven course access.

This repository reflects security hardening work performed on a **live, user-facing application**, with changes introduced incrementally to preserve production stability.

It also serves as a **backend security hardening case study**, showing how a legacy-style PHP application can be systematically improved to reach a moderate, portfolio-ready baseline without rewriting it into a framework.

---

## Project Type

- Core PHP
- MySQL
- Server-rendered HTML/CSS/JS
- Legacy-style architecture, manually hardened

---

## What the Application Does

Quizmania includes flows for:

- user authentication
- profile management
- product/course access
- MCQ practice and moderation
- purchase and assignment flows
- notifications
- chat/discussion features
- admin reporting and moderation tools

---

## Security Hardening Highlights

This project was hardened with a production-aware security mindset. Key improvements include:

- **POST-only destructive actions**
  - removed state-changing GET flows
- **CSRF protection**
  - applied to forms and AJAX endpoints
- **Prepared statements**
  - added across critical auth, admin, moderation, and purchase handlers
- **Session hardening**
  - strict cookie/session configuration, token validation, single-device enforcement
- **Authentication hardening**
  - password hashing, throttling, session regeneration
- **XSS mitigation**
  - output escaping on key pages rendering user-controlled data
- **Internal file access protection**
  - `.htaccess` rules block direct access to internal `/src` PHP files
- **Attack surface reduction**
  - unused legacy endpoints disabled with `410 Gone`

Detailed documentation:
- [`docs/HARDENING.md`](docs/HARDENING.md)
- [`docs/SECURITY.md`](docs/SECURITY.md)
- [`docs/CHANGELOG.md`](docs/CHANGELOG.md)

---

## Why This Project Matters

This is not a framework demo.  
It is a **manual hardening exercise on a legacy Core PHP codebase**.

That means the work required understanding and implementing, by hand:

- CSRF defenses
- prepared statements
- authorization checks
- safer redirect patterns
- session protections
- AJAX endpoint safety
- output escaping on rendered pages

This project demonstrates backend security awareness in an environment where protections are **not automatically provided by a framework**.

---

## Repository Structure

```text
quizmania/
├─ docs/
├─ secure/
├─ src/
│  ├─ api/
│  ├─ css/
│  ├─ db/
│  ├─ img/
│  ├─ inc/
│  ├─ js/
│  ├─ security/
│  └─ ...
├─ *.php
└─ .htaccess
````

---

## Local Setup

### Requirements

* PHP
* MySQL
* Apache / XAMPP / similar local server stack

### Database config

This repository does **not** include real credentials.

The app expects a private config file at:

```text
secure/db.php
```

Create your own based on:

```text
secure/db.example.php
```

Example structure:

```php
<?php
return [
    'host' => 'localhost',
    'user' => 'your_database_user',
    'pass' => 'your_database_password',
    'name' => 'your_database_name',
];
```

---

## Important Security Note

Real secrets are intentionally excluded from version control.

Do not commit:

* real database credentials
* `.env` files
* private config files
* logs or deployment-specific verification files

Use `secure/db.example.php` as a template for local/private configuration. Do not commit real credentials.

---

## Current Status

This hardening phase is paused after a major security pass.
The project is in a much safer state than the original version and is suitable as a **portfolio-level backend hardening project**.

Planned future improvements include:

* broader prepared-statement coverage
* global security headers
* CSP rollout
* centralized authorization helpers
* lightweight automated scanning

---

## Documentation

* **Hardening details:** [`docs/HARDENING.md`](docs/HARDENING.md)
* **Security model and disclosure guidance:** [`docs/SECURITY.md`](docs/SECURITY.md)
* **Change history:** [`docs/CHANGELOG.md`](docs/CHANGELOG.md)

---

## Author

**Rewant Khadka**

GitHub: [khadkarewant](https://github.com/khadkarewant)
