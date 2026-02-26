# 🎬 Swipeszter

> TikTok-szerű vertikális videómegosztó platform — pörgetős, mint a szél 💨

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel)
![Vue](https://img.shields.io/badge/Vue-3-42b883?style=flat-square&logo=vue.js)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)

---

## ✨ Funkciók

- 📱 **Vertikális swipe feed** — scroll-snap, autoplay, infinite scroll
- 🎬 **HLS videó streaming** — FFmpeg transzkódolás, cross-browser (hls.js + natív Safari)
- ❤️ **Like rendszer** — double-tap animációval
- 💬 **Kommentek** — nested válaszok, slide-up panel
- 👤 **Követés** — felhasználók követése
- #️⃣ **Hashtag rendszer** — kattintható linkek, trending sáv
- 🔔 **Értesítések** — real-time (Laravel Reverb WebSocket)
- 📤 **Videó feltöltés** — drag & drop, upload progress bar
- 🛡️ **Admin panel** — Filament, moderáció, statisztikák
- 🌙 **Sötét UI** — fekete/piros TikTok-stílusú dizájn

---

## 🛠️ Tech stack

| Réteg | Technológia |
|-------|-------------|
| **Backend** | Laravel 11, PHP 8.4 |
| **Frontend** | Vue 3, Pinia, Vue Router, Vite |
| **Auth** | Laravel Sanctum (token-based API) |
| **Videó** | FFmpeg → HLS, hls.js |
| **Queue** | Laravel Horizon + Redis |
| **WebSocket** | Laravel Reverb |
| **Admin** | Filament 3 |
| **DB** | MySQL 8 / PostgreSQL |
| **Cache/Session** | Redis |
| **Container** | Docker (PHP 8.4-fpm-alpine + nginx + supervisor) |
| **CI/CD** | GitHub Actions → ghcr.io |

---

## 🚀 Gyors start (Docker)

```bash
# 1. Klónozás
git clone https://github.com/razzolibot/swipeszter.git
cd swipeszter

# 2. .env beállítása
cp .env.example .env

# 3. Indítás
docker compose up -d

# 4. Inicializálás
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
docker compose exec app php artisan db:seed --class=AdminSeeder
```

Az app elérhető: **http://localhost:8080**
Admin panel: **http://localhost:8080/admin**

---

## 💻 Fejlesztői környezet

### Követelmények
- PHP 8.4+
- Node.js 20+
- MySQL 8 / PostgreSQL
- Redis
- FFmpeg

```bash
# Függőségek
composer install
npm install

# Környezet
cp .env.example .env
php artisan key:generate

# Adatbázis
php artisan migrate
php artisan db:seed --class=AdminSeeder

# Assets (dev mode hot reload-dal)
npm run dev

# Queue worker
php artisan horizon

# WebSocket server
php artisan reverb:start

# Dev szerver
php artisan serve
```

---

## ⚙️ Környezeti változók

### Kötelező
```env
APP_KEY=                    # php artisan key:generate
APP_URL=https://example.com

DB_CONNECTION=pgsql         # vagy mysql
DB_HOST=127.0.0.1
DB_DATABASE=swipeszter
DB_USERNAME=swipeszter
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
```

### Reverb (WebSocket)
```env
REVERB_APP_ID=swipeszter
REVERB_APP_KEY=your-key
REVERB_APP_SECRET=your-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### FFmpeg
```env
FFMPEG_BINARIES=/usr/bin/ffmpeg    # alapértelmezett: ffmpeg
FFPROBE_BINARIES=/usr/bin/ffprobe
```

---

## 🗄️ Adatbázis struktúra

```
users              — id, name, username, email, avatar, bio
videos             — id, user_id, title, description, hls_path, thumbnail_path, duration, status
likes              — user_id, video_id (unique)
comments           — id, user_id, video_id, parent_id, content
follows            — follower_id, following_id (unique)
video_views        — video_id, user_id, ip, watched_percent
hashtags           — id, name, slug, videos_count
hashtag_video      — hashtag_id, video_id (pivot)
notifications      — uuid, type, notifiable, data, read_at
admins             — id, name, email, password
```

---

## 🔌 API végpontok

### Auth
```
POST   /api/register
POST   /api/login
GET    /api/me                    🔒
POST   /api/logout                🔒
```

### Videók
```
GET    /api/videos                For You feed
POST   /api/videos                🔒 Feltöltés (multipart)
GET    /api/videos/{id}
DELETE /api/videos/{id}           🔒
POST   /api/videos/{id}/like      🔒 Toggle
POST   /api/videos/{id}/view
```

### Kommentek
```
GET    /api/videos/{id}/comments
POST   /api/videos/{id}/comments  🔒
DELETE /api/comments/{id}         🔒
```

### Közösség
```
POST   /api/users/{id}/follow     🔒 Toggle
GET    /api/users/{username}      Profil
PATCH  /api/profile               🔒
```

### Hashtagek
```
GET    /api/hashtags/trending
GET    /api/hashtags/{slug}
GET    /api/hashtags/{slug}/videos
```

### Értesítések (🔒 mind)
```
GET    /api/notifications
GET    /api/notifications/unread-count
POST   /api/notifications/read-all
PATCH  /api/notifications/{id}/read
```

---

## 🎬 Videó feldolgozás

A feltöltött videók háttérben dolgozódnak fel a `ProcessVideo` job-ban:

```
Feltöltés (mp4/mov)
    → Queue-ba kerül
    → FFmpeg: skálázás 720p-re
    → HLS szegmensek generálása (4mp-es .ts fájlok)
    → Thumbnail mentése (3. másodpercnél)
    → Status: pending → processing → ready
```

HLS lejátszás:
- **Safari** — natív támogatás, hls.js nem töltődik be
- **Chrome/Firefox/Edge** — hls.js lazy load

---

## 🔔 Értesítések

Real-time értesítések Laravel Reverb WebSocket-en keresztül:

| Esemény | Értesítés |
|---------|-----------|
| ❤️ Like | `@user lájkolta a videódat` |
| 💬 Komment | `@user hozzászólt: "szöveg..."` |
| 👤 Követés | `@user elkezdett követni téged` |

Az értesítések queue-ban futnak (`ShouldQueue`), így nem lassítják az API válaszidőt.

---

## 🛡️ Admin panel

Elérhető: `/admin`

| Szekció | Funkciók |
|---------|---------|
| 📊 Dashboard | Felhasználók, videók, megtekintések, lájkok, kommentek valós idejű statisztikái |
| 👤 Felhasználók | Keresés, szűrés, profil megtekintés, törlés |
| 🎬 Videók | Thumbnail előnézet, státusz badge, elrejt/megjelenít toggle, törlés |
| 💬 Kommentek | Moderáció, bulk törlés |
| #️⃣ Hashtagek | Trending sorrend, törlés |

**Első belépés után változtasd meg a jelszót!**

---

## 🐳 Docker & CI/CD

### Docker Compose (lokális)
```bash
docker compose up -d       # indítás
docker compose down        # leállítás
docker compose logs -f app # logok
```

### GitHub Actions
Minden `main` branch-re push esetén:
1. 🧪 Tesztek futnak (PHP 8.4 + MySQL + Redis)
2. 🐳 Docker image épül
3. 📤 Push → `ghcr.io/razzolibot/swipeszter:latest`

### Deploy
```bash
# Image húzása
docker pull ghcr.io/razzolibot/swipeszter:latest

# Indítás
docker compose -f docker-compose.yml up -d

# Migráció + seeder
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=AdminSeeder
```

---

## 📁 Projekt struktúra

```
app/
├── Filament/           # Admin panel (Resources, Widgets)
├── Http/Controllers/
│   └── Api/            # REST API controllerek
├── Jobs/
│   └── ProcessVideo.php  # FFmpeg HLS feldolgozás
├── Models/             # Eloquent modellek
└── Notifications/      # Laravel értesítések

resources/js/
├── components/         # Vue komponensek
│   ├── VideoCard.vue       # HLS lejátszó + interakciók
│   ├── CommentPanel.vue    # Slide-up komment panel
│   ├── NotificationPanel.vue
│   ├── TrendingHashtags.vue
│   └── HashtagText.vue     # Kattintható #hashtagek
├── stores/             # Pinia state management
│   ├── auth.js
│   ├── feed.js
│   └── notifications.js
└── views/              # Oldalak
    ├── FeedView.vue        # Főoldal (swipe feed)
    ├── HashtagView.vue
    ├── ProfileView.vue
    └── UploadView.vue

docker/
├── nginx/              # Nginx konfig (HLS streaming support)
└── supervisor/         # php-fpm + nginx + horizon
```

---

## 📄 Licenc

MIT License — csináld amit akarsz, csak ne vedd el a hírnevem 😄

---

<p align="center">
  Készítette ❤️ <a href="https://github.com/razzolibot">razzolibot</a>
</p>
