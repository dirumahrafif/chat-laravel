# TempChat

**Temporary anonymous chat room** — no login, no registration, no personal data stored.

Built with Laravel 13 + TailwindCSS v4 + Vite + Axios + MySQL.

## Features

- Create a room, share the link, start chatting instantly
- Room auto-expires after 24 hours (all data cascade-deleted)
- Real-time chat via AJAX polling every 2 seconds
- Typing indicator with debounce + polling
- Online users count (last seen < 30s)
- Countdown timer (turns red when < 1 hour left)
- Sound notification toggle (localStorage)
- Copy link & share (WhatsApp, Telegram)
- Toast notifications
- System messages (joined / left)

## Requirements

- PHP ^8.3
- MySQL
- Node.js + npm

## Setup

```bash
cp .env.example .env
# edit .env — set DB_* for MySQL

composer install
npm install

php artisan key:generate
php artisan migrate

npm run build
```

## Run

```bash
composer run dev
```

This starts four processes concurrently: Laravel server, queue worker, logs, and Vite HMR.

Alternatively start them individually:

```bash
php artisan serve     # http://localhost:8000
npm run dev           # Vite HMR
```

## Cleanup

Expired rooms are deleted automatically every hour via the Laravel scheduler:

```bash
php artisan schedule:run        # manual trigger
php artisan rooms:cleanup        # run the cleanup command directly
```

## Design

"Zed Dev" palette — warm near-black, cyan accent, flat colors, JetBrains Mono + Inter.

| Token | Hex |
|-------|-----|
| Neutral | `#161614` |
| Surface | `#1D1D1B` |
| Primary | `#E5E4DF` |
| Secondary | `#7B7A74` |
| Tertiary | `#5FB5D6` |

## Project Structure

```
app/
├── Console/Commands/RoomsCleanup.php   # hourly expired room cleanup
├── Http/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── RoomController.php
│   │   ├── ChatUserController.php
│   │   └── MessageController.php
│   └── Middleware/CheckRoomExpired.php # room expiry guard
└── Models/
    ├── Room.php
    ├── ChatUser.php
    └── Message.php

resources/
├── css/app.css     # TailwindCSS v4 theme
├── js/app.js       # axios config + defaults
└── views/
    ├── layouts/app.blade.php
    ├── home.blade.php
    └── room.blade.php
```

## License

MIT
