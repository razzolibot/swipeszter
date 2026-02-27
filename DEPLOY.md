# 🚀 Swipeszter — Szerveres telepítési útmutató

> Traefik 3.6 + Docker Compose alapú production deployment  
> Szerver: Debian 13, 80/443 port szabad kell legyen

---

## 🗂 Architektúra áttekintés

```
Internet
   │
   ▼
Traefik 3.6  (port 80/443, Let's Encrypt TLS)
   │
   ▼ HTTPS → HTTP (TLS terminálás)
┌─────────────────────────────────────────────┐
│  app konténer                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │  nginx   │  │ php-fpm  │  │ horizon  │  │
│  │  :80     │  │  :9000   │  │ (queue)  │  │
│  └────┬─────┘  └──────────┘  └──────────┘  │
│       │ /app/* WebSocket proxy              │
│       ▼                                     │
│  ┌──────────┐                               │
│  │  reverb  │ (WebSocket, :8080 belső)      │
│  └──────────┘                               │
└─────────────────────────────────────────────┘
       │                    │
  ┌────▼────┐          ┌────▼────┐
  │  MySQL  │          │  Redis  │
  │  :3306  │          │  :6379  │
  └─────────┘          └─────────┘

Hálózatok:
  traefik  → Traefik ↔ app (external network)
  internal → app ↔ MySQL ↔ Redis (privát)
```

---

## 📋 Előfeltételek

- **Docker** + **Docker Compose** (v2+) telepítve
- **Domain** beállítva → szerver IP-re (A rekord)
- **80 és 443 port** szabad a szerveren
- **Git** telepítve

---

## 1️⃣ Traefik beállítása (egyszer kell, minden app előtt)

```bash
# 1. Docker hálózat létrehozása
docker network create traefik

# 2. ACME (Let's Encrypt) tároló
mkdir -p /opt/traefik/acme
touch /opt/traefik/acme/acme.json
chmod 600 /opt/traefik/acme/acme.json

# 3. Traefik indítása
cd /opt   # vagy ahova akarod
git clone https://github.com/razzolibot/swipeszter.git  # vagy másold oda a fájlt

docker compose -f swipeszter/docker/traefik/docker-compose.yml up -d

# Ellenőrzés
docker compose -f swipeszter/docker/traefik/docker-compose.yml ps
docker logs traefik-traefik-1 --tail=20
```

> **Megjegyzés:** Az email cím a `docker/traefik/docker-compose.yml`-ben van beégetve (`razzolibot@gmail.com`). Módosítsd ha más emailt akarsz Let's Encrypt értesítésekhez.

---

## 2️⃣ Swipeszter konfigurálása

```bash
# Klónozd a repót a szerverre
cd /opt
git clone https://github.com/razzolibot/swipeszter.git
cd swipeszter

# Töltsd ki a .env.prod fájlt
cp .env.prod .env.prod.local
nano .env.prod.local
```

### Mit kell kitölteni a `.env.prod.local`-ban:

| Változó | Példa | Leírás |
|---------|-------|--------|
| `DOMAIN` | `swipeszter.hu` | A te domainedet |
| `APP_KEY` | `base64:xyz...` | Laravel titkosítási kulcs (generálás lent) |
| `DB_PASSWORD` | `Erős_jelszó_123` | MySQL jelszó |
| `DB_ROOT_PASSWORD` | `Root_jelszó_456` | MySQL root jelszó |
| `REVERB_APP_ID` | `100001` | Tetszőleges szám |
| `REVERB_APP_KEY` | `abc123xyz` | WebSocket azonosító kulcs |
| `REVERB_APP_SECRET` | `titkos123` | WebSocket titkos |

#### APP_KEY generálása:
```bash
docker run --rm php:8.4-alpine php -r \
  "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

#### Reverb kulcsok generálása:
```bash
openssl rand -hex 16   # REVERB_APP_KEY-hez
openssl rand -hex 24   # REVERB_APP_SECRET-hez
```

---

## 3️⃣ Build + indítás

```bash
cd /opt/swipeszter

# Build (ez eltart 5-10 percig első alkalommal — FFmpeg, npm, composer miatt)
docker compose -f docker-compose.prod.yml --env-file .env.prod.local build

# Indítás
docker compose -f docker-compose.prod.yml --env-file .env.prod.local up -d

# Státusz
docker compose -f docker-compose.prod.yml --env-file .env.prod.local ps
```

Az entrypoint automatikusan lefuttatja:
- `php artisan migrate --force`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan storage:link`

---

## 4️⃣ Admin felhasználó létrehozása

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  exec app php artisan db:seed --class=AdminSeeder
```

Alapértelmezett admin: `admin@swipeszter.hu` / `swipeszter2026`  
⚠️ **Első belépés után azonnal változtasd meg a jelszót!**  
Admin panel: `https://swipeszter.hu/admin`

---

## 5️⃣ Ellenőrzés

```bash
# Logok figyelése
docker compose -f docker-compose.prod.yml --env-file .env.prod.local logs -f app

# Horizon (queue worker) státusz
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  exec app php artisan horizon:status

# Reverb WebSocket státusz
curl -s https://swipeszter.hu/app/REVERB_APP_KEY | head -5
```

---

## 🔄 Frissítés (új verzió deploy)

```bash
cd /opt/swipeszter
git pull

# Újrabuildelés (csak az app container változott általában)
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  build app

# Zero-downtime restart (régi konténer fut, amíg az új elindul)
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  up -d --no-deps app
```

---

## 🗄 Adatmentés

```bash
# MySQL dump
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  exec mysql mysqldump -u swipeszter -p swipeszter > backup_$(date +%Y%m%d).sql

# Storage (videók, képek) mentése
docker run --rm \
  -v swipeszter_storage_data:/data \
  -v $(pwd)/backups:/backup \
  alpine tar czf /backup/storage_$(date +%Y%m%d).tar.gz -C /data .
```

---

## 🛠 Hasznos parancsok

```bash
# Shell a konténerbe
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  exec app sh

# Artisan parancs futtatása
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  exec app php artisan <parancs>

# Cache ürítés
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  exec app php artisan cache:clear

# Konténer újraindítása (pl. config változtatás után)
docker compose -f docker-compose.prod.yml --env-file .env.prod.local \
  restart app

# Leállítás (adatok megmaradnak a volume-okban)
docker compose -f docker-compose.prod.yml --env-file .env.prod.local down
```

---

## 📁 Fontosabb fájlok

```
swipeszter/
├── docker-compose.prod.yml     ← Production compose (Traefik labelekkel)
├── docker-compose.yml          ← Helyi fejlesztés (MySQL + Redis + port 8080)
├── .env.prod                   ← Sablon (.env.prod.local a valódi, git-ignorált)
├── Dockerfile                  ← nginx + php-fpm + horizon + reverb egy konténerben
├── docker/
│   ├── entrypoint.sh           ← Migráció + cache boot-on
│   ├── nginx/default.conf      ← Web + /app/ WebSocket proxy
│   ├── supervisor/             ← php-fpm + nginx + horizon + reverb
│   └── traefik/docker-compose.yml  ← Traefik 3.6 stack (egyszer indul a szerveren)
```

---

## 🔒 Biztonsági javaslatok

1. **Soha ne commitold a `.env.prod.local`-t** — add hozzá `.gitignore`-hoz
2. **Jelszavak**: min. 20 karakter, special karakterek
3. **Admin jelszó** első belépés után azonnal cseréld le
4. **SSH**: kulcs-alapú auth, jelszó auth tiltva
5. **Firewall**: csak 80, 443 (és SSH 22) legyen nyitva

```bash
# .gitignore ellenőrzés
echo ".env.prod.local" >> .gitignore
```
