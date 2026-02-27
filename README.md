# 📱 Swipeszter — TikTok-klón videómegosztó platform

> **Mobil-first, full-screen, vertikálisan görgethető videóplatform**  
> Stack: Laravel 11 + Vue 3 + MySQL + Redis + FFmpeg + Reverb WebSocket

---

## ✨ Funkciók

### 📹 Videók
- **Feltöltés** — videó feltöltés FFmpeg-alapú automatikus HLS-transzkódolással
- **Feed** — végtelen görgethető full-screen videó feed
- **HLS streaming** — adaptív bitráta lejátszás (hls.js + natív Safari)
- **Thumbnail** — automatikus bélyegképgenerálás az első keyframe-ből
- **Double-tap like** — dupla érintés → ❤️ animáció

### ❤️ Interakció
- **Like/unlike** — videók kedvelése (toggle)
- **Kommentek** — komment thread, válaszok támogatásával
- **Követés** — felhasználók követése/nem követése
- **Nézettség** — megtekintési szám nyilvántartás

### #️⃣ Hashtagek
- **Auto-extract** — `#hashtag` automatikusan kinyerve a leírásból feltöltéskor
- **Trending sáv** — top 10 hashtag a feed és hashtag oldalakon
- **Hashtag feed** — `/hashtag/:slug` — szűrt videólista

### 🔔 Értesítések (real-time)
- **Like, komment, követés** eseményekre automatikus értesítés
- **Reverb WebSocket** — azonnali push (nem polling!)
- **DB perzisztencia** — értesítések olvasottra jelölhetők
- **Notification bell** — 🔔 badge az olvasatlan darabszámmal, slide-up panel

### 👤 Profil
- Avatar, bio, felhasználónév szerkesztése
- Saját videók listája
- Követők/követett számláló

### 🛡 Admin (Filament)
- **URL**: `/admin`
- **Felhasználókezelés** — moderálás, ban
- **Videókezelés** — törlés, státusz
- **Komment moderálás**
- **Hashtag kezelés**
- **Dashboard** — 6 statisztikai widget (videók, felhasználók, like-ok, kommentek, nézettsége, feldolgozás alatt)

---

## 🏗 Technikai felépítés

### Backend (Laravel 11)
```
app/
├── Http/Controllers/Api/
│   ├── AuthController.php         # Sanctum token auth
│   ├── VideoController.php        # CRUD + feed + view count
│   ├── LikeController.php         # Toggle like + értesítés
│   ├── CommentController.php      # Komment + törlés + értesítés
│   ├── FollowController.php       # Követés toggle + értesítés
│   ├── ProfileController.php      # Profil megtekintés + szerkesztés
│   ├── HashtagController.php      # Trending + hashtag feed
│   └── NotificationController.php # Értesítés lista + olvasás
├── Models/
│   ├── User.php (HasApiTokens, Notifiable)
│   ├── Video.php
│   ├── Like.php, Comment.php, VideoView.php
│   └── Hashtag.php (videos_count counter)
├── Notifications/
│   ├── LikeNotification.php       # DB + Broadcast, ShouldQueue
│   ├── CommentNotification.php
│   └── FollowNotification.php
├── Jobs/
│   └── ProcessVideo.php           # FFmpeg → HLS + thumbnail
└── Filament/Resources/            # Admin panel
    ├── UserResource.php
    ├── VideoResource.php
    ├── CommentResource.php
    └── HashtagResource.php
```

### Frontend (Vue 3 + Pinia + Vue Router)
```
resources/js/
├── stores/
│   ├── auth.js                    # Bejelentkezés állapot
│   ├── feed.js                    # Videó feed, végtelen görgetés
│   └── notifications.js           # Értesítések, unread count
├── views/
│   ├── FeedView.vue               # Full-screen feed, TikTok stílus
│   ├── LoginView.vue / RegisterView.vue
│   ├── UploadView.vue
│   ├── ProfileView.vue
│   └── HashtagView.vue
├── components/
│   ├── VideoCard.vue              # Full-screen videó, like animáció
│   ├── CommentPanel.vue           # Komment slide-up panel
│   ├── NotificationPanel.vue      # Értesítés slide-up panel
│   ├── TrendingHashtags.vue       # Trending hashtag pill-sáv
│   └── HashtagText.vue            # Kattintható #hashtag linkek
└── echo.js                        # Laravel Echo + Reverb konfig
```

### Infrastruktúra
```
Dockerfile          — PHP 8.4-fpm + nginx + FFmpeg + supervisor (egy konténer)
docker-compose.yml  — Helyi fejlesztés (MySQL + Redis, port 8080)
docker-compose.prod.yml — Production (Traefik labelekkel, belső hálózat)
docker/
├── nginx/default.conf       — Web + /app/ WebSocket proxy (→ Reverb)
├── supervisor/supervisord.conf — php-fpm + nginx + horizon + reverb
├── entrypoint.sh            — Boot: migrate + cache + storage:link
└── traefik/docker-compose.yml — Traefik 3.6 stack (Let's Encrypt)
```

---

## 🗄 Adatbázis séma

| Tábla | Leírás |
|-------|--------|
| `users` | Felhasználók (username, avatar, bio, followers_count, following_count) |
| `videos` | Videók (hls_path, thumbnail_path, status, likes_count, comments_count, views_count) |
| `likes` | user_id + video_id (unique) |
| `comments` | user_id, video_id, parent_id (thread), content |
| `follows` | follower_id, following_id pivot |
| `video_views` | user_id, video_id, watched_percent |
| `hashtags` | name, slug, videos_count |
| `hashtag_video` | pivot tábla |
| `notifications` | UUID PK, type, data JSON, read_at |
| `personal_access_tokens` | Sanctum tokenek |

---

## 🚀 Gyorsindítás (helyi fejlesztés)

```bash
git clone https://github.com/razzolibot/swipeszter.git
cd swipeszter

# .env másolás és kulcs generálás
cp .env.example .env
# DB, Redis beállítása a .env-ben (MySQL: swipeszter/swipeszter)

# Docker indítás
docker compose up -d --build

# Elérhető: http://localhost:8080
```

---

## 🌍 Production deploy

Lásd: **[DEPLOY.md](DEPLOY.md)** — részletes útmutató Traefik 3.6-tal

```bash
# Röviden:
docker network create traefik
docker compose -f docker/traefik/docker-compose.yml up -d
cp .env.prod .env.prod.local && nano .env.prod.local
docker compose -f docker-compose.prod.yml --env-file .env.prod.local build
docker compose -f docker-compose.prod.yml --env-file .env.prod.local up -d
```

---

## 📊 CI/CD

GitHub Actions (`.github/workflows/ci-cd.yml`):
1. **Tests** — `php artisan test` SQLite in-memory-n
2. **Build** — Docker image buildelés
3. **Push** → `ghcr.io/razzolibot/swipeszter:latest`

---

## 🔐 Admin hozzáférés

```
URL:      https://<domain>/admin
Email:    admin@swipeszter.hu
Jelszó:   swipeszter2026
```
> ⚠️ Első belépés után azonnal változtasd meg!
