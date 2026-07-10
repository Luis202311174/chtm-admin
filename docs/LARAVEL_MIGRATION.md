# CHTM Laravel Application

A modern hotel management system built with Laravel 12, Inertia.js, and Vue 3.

## Tech Stack

| Layer | Technology |
|-------|------------|
| HTTP | `routes/web.php` + `routes/auth.php` |
| UI | Inertia.js, Vue 3, Tailwind CSS, Tabler Icons |
| Data | Eloquent, SQLite/MySQL |
| Sensitive fields | AES-256-GCM (`Aes256GcmEncrypted` cast) |
| Auth | Session guard, `email_hash` login lookup |

## Pages

| URL | Controller |
|-----|------------|
| `/login` | `AuthenticatedSessionController` |
| `/dashboard` | `DashboardController` |
| `/reservation` | `ReservationController` |
| `/frontoffice` | `FrontOfficeController` |
| `/room` | `RoomController` |
| `/archived` | `ArchivedController` |
| `/audit` | `AuditController` |
| `/settings` | `SettingsController` |
| `/profile` | `ProfileController` |

## Development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
npm run dev
```

## Default Users

- `admin@chtm.local` / `password` (super_admin)
- `reservation@chtm.local` / `password` (reservation)
- `frontoffice@chtm.local` / `password` (frontoffice)
- `housekeeper@chtm.local` / `password` (housekeeper)

## Environment

Set `AES_GCM_KEY` in production (32-byte key, base64-encoded with the `base64:` prefix).