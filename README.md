# CHTM Admin

A modern hotel management system built with Laravel 12, Inertia.js, and Vue 3.

## Features

- **Dashboard**: Real-time room occupancy stats and upcoming bookings
- **Reservations**: Manage booking requests, approvals, check-ins, and check-outs
- **Front Office**: Guest management and receipt handling
- **Room Management**: Room inventory, status tracking, and housekeeping tasks
- **Archived Bookings**: Historical records and revenue tracking
- **Audit & Reports**: Sales reports, guest statistics, and activity logs
- **Settings**: Notification preferences and security settings

## Tech Stack

| Layer | Technology |
|-------|------------|
| HTTP | `routes/web.php` + `routes/auth.php` |
| UI | Inertia.js, Vue 3, Tailwind CSS, Tabler Icons |
| Data | Eloquent, SQLite/MySQL |
| Sensitive fields | AES-256-GCM encryption |
| Auth | Session guard, role-based access |

## Development

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed

# Start development servers
php artisan serve
npm run dev
```

## Default Users

| Email | Role |
|-------|------|
| `admin@chtm.local` | super_admin |
| `reservation@chtm.local` | reservation |
| `frontoffice@chtm.local` | frontoffice |
| `housekeeper@chtm.local` | housekeeper |

Password: `password`

## Project Structure

```
app/
├── Http/
│   ├── Controllers/     # Page controllers
│   └── Middleware/      # Request middleware
├── Models/              # Eloquent models
├── Services/            # Business logic
└── Casts/               # Custom attribute casts

resources/
├── js/
│   ├── Pages/           # Vue page components
│   └── Layouts/         # Shared layouts
└── css/                 # Tailwind CSS

routes/
└── web.php              # Web routes
```

## Available Scripts

- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run lint` - Lint JavaScript/Vue files
- `npm run format` - Format code with Prettier