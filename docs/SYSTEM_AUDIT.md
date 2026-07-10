# CHTM System Audit

Audit date: June 2026

## Verdict

The staff application runs entirely on **Laravel 12** with **Inertia.js** and **Vue 3** for a modern SPA-like experience.

## Runtime Stack

| Layer | Technology |
|-------|------------|
| HTTP | `routes/web.php` + `routes/auth.php` |
| UI | Inertia.js, Vue 3, Tailwind CSS, Tabler Icons |
| Data | Eloquent, SQLite/MySQL |
| Sensitive fields | AES-256-GCM (`Aes256GcmEncrypted` cast) |
| Auth | Session guard, `email_hash` login lookup |

## Checks Performed

- All controllers properly use Inertia for rendering
- Vue 3 components in `resources/js/Pages/`
- Tailwind CSS for styling
- PHPUnit tests passing