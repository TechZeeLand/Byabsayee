# Byabsayee — Self-Contained Stack Setup
## Complete migration guide — everything inside /Sites/byabsayee

---

## What changes

**Before:** Byabsayee depended on your shared nginx, php-fpm, and MariaDB containers.

**After:** Byabsayee has its own nginx, PHP-FPM, and MariaDB — all in one docker-compose.yml.
Your other stacks (nginx, MariaDB, other sites) are completely untouched.

**Everything lives in:**
```
/Sites/byabsayee/
├── app/                    ← PHP application code
├── config/                 ← Configuration
├── public/                 ← Web root (nginx serves this)
├── views/                  ← HTML templates
├── routes.php              ← URL routing
├── uploads/                ← ALL file uploads (logos, products, receipts)
│   ├── logos/
│   ├── products/
│   ├── attachments/
│   ├── receipts/
│   └── proofs/
├── storage/
│   ├── sessions/           ← PHP sessions (fixes cross-device CSRF)
│   └── logs/               ← PHP error logs
├── data/                   ← Auto daily SQL backups (byabsayee_db_YYYY-MM-DD.sql)
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf      ← Nginx config
│   │   └── logs/           ← Nginx access/error logs
│   └── mariadb/
│       ├── data/           ← MariaDB database files
│       └── init/           ← SQL files run on first start
├── assets/
│   └── images/
│       ├── ByabsayeeLogo.png
│       └── ByabsayeeLogo.svg
├── Dockerfile              ← PHP-FPM image
└── docker-compose.yml      ← Single file — all 4 services
```

---

## Step 1 — Run setup script

```bash
bash /Sites/byabsayee/setup.sh
```

This creates all directories and sets correct permissions.

---

## Step 2 — Copy new files

```bash
# Copy all new PHP files
cp -r app/         /Sites/byabsayee/app/
cp -r views/       /Sites/byabsayee/views/
cp -r config/      /Sites/byabsayee/config/
cp    routes.php   /Sites/byabsayee/routes.php

# Copy Docker files
cp Dockerfile               /Sites/byabsayee/Dockerfile
cp docker-compose.yml       /Sites/byabsayee/docker-compose.yml
cp docker/nginx/nginx.conf  /Sites/byabsayee/docker/nginx/nginx.conf

# Copy CSS
cp public/css/app.css   /Sites/byabsayee/public/css/app.css
cp public/css/auth.css  /Sites/byabsayee/public/css/auth.css

# Run schema files in order
# (do this in phpMyAdmin or via CLI below)
```

---

## Step 3 — Remove old byabsayee nginx config

Your shared nginx currently has a config for byabsayee on port 1011.
Since byabsayee now has its OWN nginx on port 1011, remove the old one:

```bash
rm /opt/stacks/webserver/nginx/sites-enabled/byabsayee.conf
rm /opt/stacks/webserver/nginx/sites-available/byabsayee.conf

# Reload shared nginx
docker exec nginx nginx -s reload
```

---

## Step 4 — Stop old byabsayee container (if running)

```bash
docker stop byabsayee 2>/dev/null || true
docker rm   byabsayee 2>/dev/null || true
```

In Portainer: delete the old `byabsayee` stack entirely.

---

## Step 5 — Import your database into the new container

You need to move your data from the old `byabsayee_db` (in shared MariaDB)
to the new `byabsayee-db` container.

**Option A — Export from phpMyAdmin then import:**
1. phpMyAdmin → select `byabsayee_db` → Export → SQL format → Go
2. Save the file as `/Sites/byabsayee/docker/mariadb/init/byabsayee_db.sql`
3. This file will be auto-imported when byabsayee-db starts for the first time

**Option B — CLI export:**
```bash
docker exec mariadb sh -c \
  'mariadb-dump -u root -pRAYilyvm2005@ byabsayee_db' \
  > /Sites/byabsayee/docker/mariadb/init/byabsayee_db.sql

echo "Exported — check file size:"
ls -lh /Sites/byabsayee/docker/mariadb/init/byabsayee_db.sql
```

---

## Step 6 — Move uploads from old location

Your uploads were in `/raids/byabsayee-uploads/`. Move them inside the project:

```bash
# Copy everything to new location
cp -r /raids/byabsayee-uploads/. /Sites/byabsayee/uploads/

# Fix permissions for PHP-FPM (www-data = uid 82 on Alpine)
chown -R 82:82 /Sites/byabsayee/uploads
chmod -R 755   /Sites/byabsayee/uploads

echo "Uploads moved. Checking:"
ls -la /Sites/byabsayee/uploads/
```

---

## Step 7 — Run new schema files

Also add the new SQL to the init folder so they run on first start,
OR run them manually after starting:

```bash
# Copy schema files to init folder (they run alphabetically on first start)
cp /Sites/byabsayee/schema-phase7.sql \
   /Sites/byabsayee/docker/mariadb/init/02_phase7.sql
```

Or run manually after Step 8:
```bash
docker exec -i byabsayee-db mariadb \
  -u byabsayee -pRAYilyvm2005@ byabsayee_db \
  < /Sites/byabsayee/schema-phase7.sql
```

---

## Step 8 — Deploy in Portainer

**In Portainer → Stacks → Add stack:**

1. Name: `byabsayee`
2. Build method: **Repository**
3. Repository URL: `https://github.com/TechZeeLand/Byabsayee`
4. Reference: `refs/heads/main`
5. Compose path: `docker-compose.yml`
6. Enable authentication → your GitHub token
7. Click **Deploy the stack**

Portainer will build the PHP image and start all 4 containers:
- `byabsayee-db` — MariaDB
- `byabsayee-php` — PHP-FPM
- `byabsayee-nginx` — Nginx on port 1011
- `byabsayee-backup` — Daily SQL backup to `/Sites/byabsayee/data/`

---

## Step 9 — Install Composer dependencies

```bash
docker exec byabsayee-php composer install \
  --working-dir=/Sites/byabsayee \
  --no-dev \
  --optimize-autoloader
```

---

## Step 10 — Fix permissions (final)

```bash
find /Sites/byabsayee/app    -type f -exec chmod 644 {} \;
find /Sites/byabsayee/views  -type f -exec chmod 644 {} \;
find /Sites/byabsayee/public -type f -exec chmod 644 {} \;
find /Sites/byabsayee        -type d -exec chmod 755 {} \;
chown -R 82:82 /Sites/byabsayee/uploads
chown -R 82:82 /Sites/byabsayee/storage
chown -R 82:82 /Sites/byabsayee/data
```

---

## Step 11 — Test

Visit: `http://192.168.0.123:1011`

Login: `admin@byabsayee.local` / `password123`

---

## Daily automatic backups

The `byabsayee-backup` container runs `mysqldump` every midnight and saves:
```
/Sites/byabsayee/data/byabsayee_db_YYYY-MM-DD_HH-MM-SS.sql
```

Keeps last 30 days. Plain SQL files — readable, restorable, committable to git.

To restore from a backup:
```bash
docker exec -i byabsayee-db mariadb \
  -u byabsayee -pRAYilyvm2005@ byabsayee_db \
  < /Sites/byabsayee/data/byabsayee_db_2026-01-15_00-00-00.sql
```

---

## Cloudflare tunnel (when ready)

In Cloudflare Zero Trust → Tunnels → your tunnel → Public Hostnames:
- Subdomain: `byabsayee` (or your domain)
- Service: `http://byabsayee-nginx:80`
  (or `http://192.168.0.123:1011` if tunnel runs on host)

Update `APP_URL` in docker-compose.yml to your public domain.
Set `session.cookie_secure = 1` in Dockerfile once on HTTPS.

---

## Stopping / restarting just Byabsayee

```bash
# Stop everything
docker compose -f /Sites/byabsayee/docker-compose.yml down

# Start everything
docker compose -f /Sites/byabsayee/docker-compose.yml up -d

# Restart just PHP (after code changes)
docker restart byabsayee-php

# Restart just nginx (after config changes)
docker restart byabsayee-nginx

# View logs
docker logs byabsayee-php   --tail 50
docker logs byabsayee-nginx --tail 50
docker logs byabsayee-db    --tail 50
```

None of these affect your other stacks (shared nginx, MariaDB, etc.).
