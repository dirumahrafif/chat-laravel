# AGENTS.md — TempChat

## Project Overview

Temporary anonymous chat room (no login/register). Laravel 13 + TailwindCSS v4 + Vite + Vanilla JS (axios). MySQL. AJAX polling every 2s.

## Key Instruction Files

- `.agents/prd.md` — full feature specification
- `.agents/design.md` — strict UI/UX (Zed Dev palette: neutral `#161614`, surface `#1D1D1B`, primary `#E5E4DF`, secondary `#7B7A74`, tertiary `#5FB5D6`)
- `.agents/tasklist.md` — must update after each completed task with `[x]` + ✅ + progress %

## Architecture

| Layer | Details |
|-------|---------|
| Auth | Session-based (`chat_user_id` + `chat_user_name`), no login/register |
| Real-time | AJAX polling every 2s (no WebSockets) |
| Database | MySQL via `.env` credentials |
| Frontend | Blade + Inline JS + `window.axios` |

## Database

- **rooms**: id, code (unique 8-char), expired_at, timestamps
- **chat_users**: id, room_id (FK cascade), name (max 30), last_seen, typing_at
- **messages**: id, room_id (FK cascade), user_id (FK cascade -> chat_users), message (max 500)

## API Endpoints (all under `check.room.expired` middleware)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/` | Homepage |
| POST | `/room` | Create room (redirects to room page) |
| GET | `/room/{code}` | Room page |
| POST | `/room/{code}/join` | Join room (JSON) |
| POST | `/room/{code}/typing` | Update typing_at (debounced 300ms client-side) |
| GET | `/room/{code}/typing/status` | Who's typing (read-only, returns names) |
| POST | `/room/{code}/leave` | Leave room + system message |
| GET | `/room/{code}/messages` | Fetch messages + online_count |
| POST | `/room/{code}/messages` | Send message (throttle: 30/min) |

## Frontend Conventions

- **TailwindCSS v4** with `@theme` custom colors (primary/secondary/tertiary/neutral/surface/on-primary)
- **Fonts**: Google Fonts CDN — JetBrains Mono (display/h1/label), Inter (body)
- **Design rules**: single accent (tertiary `#5FB5D6`), flat colors (no gradients)
- **Inline scripts in Blade** must use `window.axios` or `api()` helper (bare `axios` not available in inline context)
- `api()` fallback helper in room.blade.php resolves `window.axios || axios`

## JavaScript Gotchas

- axios global configured in `resources/js/app.js` (X-Requested-With, Accept, Content-Type, X-CSRF-TOKEN headers)
- CSRF token auto-set from `<meta name="csrf-token">`
- Typing debounce: 300ms on `input` event before POST to `/typing`
- Sound: `AudioContext` oscillator (no audio files), preference in localStorage (`soundEnabled`)
- Smart scroll: only auto-scroll if user is already near bottom (< 50px)

## Commands

| Command | Description |
|---------|-------------|
| `php artisan rooms:cleanup` | Delete expired rooms + cascade |
| `php artisan schedule:run` | Run scheduled tasks (cleanup runs hourly) |
| `npm run build` | Build Vite assets |
| `npm run dev` | Vite dev server for HMR |
| `php artisan serve` | Laravel dev server |
| `php artisan migrate` | Run migrations |
| `composer run dev` | Concurrent: server + queue + logs + vite |

## Security

- Input: `strip_tags()` on all user input (name, message)
- Output: `e()` in JSON responses
- Max lengths: name 30 chars, message 500 chars
- Rate limit: 30 messages/minute via `throttle:30,1`
- CSRF: meta tag + axios default header
- Room expiry middleware checks on all `/room/{code}*` routes

## Models

- **Room**: `hasMany(ChatUser)`, `hasMany(Message)`, `$casts = ['expired_at' => 'datetime']` (required for `toIso8601String()` in views)
- **ChatUser**: `belongsTo(Room)`, `hasMany(Message, 'user_id')`
- **Message**: `belongsTo(Room)`, `belongsTo(ChatUser, 'user_id')`

## Key Logic

- Room code: 8-char uppercase alphanumeric (`Str::random(8)` + collision check)
- Room expires: `expired_at = now() + 24h`
- Online users: `last_seen >= now() - 30s`
- Typing detection: `typing_at >= now() - 3s`
- Duplicate name: suffix `"Name (2)"`, auto-increment
- System messages: "X joined the room" / "X left the room" stored as regular messages
