# Byabsayee — Setup Guide
## Complete step-by-step instructions for your Debian 12 home server

---

## STEP 1 — Create the project folder on your server

SSH into your server and run:

```bash
mkdir -p /Sites/byabsayee
mkdir -p /raids/byabsayee-uploads
```

---

## STEP 2 — Copy project files

Copy all files from this zip into `/Sites/byabsayee/` on your server.
Your final structure should look like:

```
/Sites/byabsayee/
├── public/
│   ├── index.php        ← nginx points here
│   └── css/
│       └── auth.css
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   └── DashboardController.php
│   └── Helpers/
│       ├── Database.php
│       ├── Router.php
│       └── helpers.php
├── config/
│   └── app.php
├── views/
│   ├── auth/
│   │   ├── layout.php
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── forgot-password.php
│   │   └── reset-password.php
│   ├── dashboard/
│   │   └── index.php
│   └── errors/
│       └── 404.php
├── routes.php
├── schema.sql
├── composer.json
└── docker-compose.yml
```

---

## STEP 3 — Create the database

1. Open phpMyAdmin in your browser (your existing one)
2. Click **"New"** in the left sidebar
3. Database name: `byabsayee_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click **Create**
6. Click the **SQL** tab
7. Paste the entire contents of `schema.sql`
8. Click **Go**

This creates all tables and one test user:
- Email: `admin@byabsayee.local`
- Password: `password123`

---

## STEP 4 — Create a database user (more secure than using root)

In phpMyAdmin → SQL tab (with no database selected):

```sql
CREATE USER 'byabsayee_user'@'%' IDENTIFIED BY 'CHOOSE_A_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON byabsayee_db.* TO 'byabsayee_user'@'%';
FLUSH PRIVILEGES;
```

Use this username and password in your docker-compose.yml environment variables.

---

## STEP 5 — Install PHP dependencies (mPDF + PHPMailer)

Your php-fpm container needs Composer. Run this inside the container:

```bash
# Enter your php-fpm container
docker exec -it php-fpm bash

# Install Composer (if not already installed)
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Go to your project and install dependencies
cd /Sites/byabsayee
composer install --no-dev --optimize-autoloader

# Exit container
exit
```

This creates the `vendor/` folder with mPDF and PHPMailer inside it.

---

## STEP 6 — Add the Docker service

In Portainer:
1. Go to **Stacks**
2. Click **Add stack**
3. Name it `byabsayee`
4. Paste the contents of `docker-compose.yml`
5. Edit the environment variables (especially DB_PASS and APP_KEY)
6. Click **Deploy the stack**

To generate a secure APP_KEY, run:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

---

## STEP 7 — Add nginx config

Copy `nginx-byabsayee.conf` to your nginx sites-available folder:

```bash
cp nginx-byabsayee.conf /opt/stacks/webserver/nginx/sites-available/byabsayee.conf

# Enable it (create a symlink in sites-enabled)
ln -s /opt/stacks/webserver/nginx/sites-available/byabsayee.conf \
      /opt/stacks/webserver/nginx/sites-enabled/byabsayee.conf
```

Then edit the file to replace:
- `byabsayee.yourdomain.com` with your actual subdomain or domain

Reload nginx:
```bash
docker exec nginx nginx -s reload
```

---

## STEP 8 — Set correct file permissions

```bash
# PHP needs to write to uploads folder
chown -R www-data:www-data /raids/byabsayee-uploads
chmod -R 755 /raids/byabsayee-uploads

# Protect config files
chmod 600 /Sites/byabsayee/config/app.php
```

---

## STEP 9 — Test it

Visit `https://yourdomain.com/login` in your browser.

You should see the Byabsayee login page.

Log in with:
- Email: `admin@byabsayee.local`
- Password: `password123`

**Change the password immediately after first login!**

---

## STEP 10 — Configure Cloudflared

In your Cloudflare Zero Trust dashboard:
1. Go to **Tunnels**
2. Add a public hostname:
   - Subdomain: `byabsayee` (or whatever you want)
   - Domain: your domain
   - Service: `http://nginx:80`
3. Save

Now `https://byabsayee.yourdomain.com` is publicly accessible through Cloudflare's tunnel — no port forwarding needed.

---

## Troubleshooting

**Blank white page?**
- Check PHP errors: `docker logs php-fpm`
- Check nginx errors: `docker logs nginx`

**"Database connection failed"?**
- Make sure `byabsayee` container is on the `web` network
- Double-check DB_HOST=mariadb matches your MariaDB container name
- Verify the database user and password

**CSS not loading?**
- Make sure the nginx root points to `/Sites/byabsayee/public`
- Check the CSS file is at `/Sites/byabsayee/public/css/auth.css`

**404 on all pages?**
- The nginx `try_files $uri $uri/ /index.php` line handles routing
- Make sure your nginx config is loaded: `docker exec nginx nginx -t`

---

## What's built so far (Phase 1)

- [x] Full folder structure
- [x] Docker + nginx configuration
- [x] Database schema (all core tables)
- [x] Router (clean URL routing)
- [x] Database helper (safe PDO queries)
- [x] Global helper functions
- [x] Auth system: register, login, logout
- [x] Email verification flow
- [x] Password reset flow
- [x] CSRF protection on all forms
- [x] Login, Register, Forgot/Reset password pages (with CSS)
- [x] Temporary dashboard

## What's coming next (Phase 2)

- [ ] Book creation and listing (personal + business)
- [ ] Personal book: contacts and entries
- [ ] Business book: customers, suppliers
- [ ] Product/stock management
- [ ] Invoice creation with PDF export
- [ ] Employee management and roles
