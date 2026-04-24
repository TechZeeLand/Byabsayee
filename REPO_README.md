# Byabsayee (ব্যবসায়ী)

A self-hosted business accounting and management platform built from scratch.

**Stack:** PHP 8.2 · MariaDB · Nginx · Docker · No frameworks

---

## Project structure

```
app/          → PHP application (deployed to /Sites/byabsayee on server)
docker/       → Dockerfile and docker-compose for the byabsayee container
```

## Features (in progress)

- [x] User auth — register, login, email verification, password reset
- [x] Personal books — income/expense entries with contacts and attachments
- [x] Business books — customers, suppliers, products, invoices
- [ ] Invoice PDF generation
- [ ] Employee management and payroll
- [ ] Delivery tracking
- [ ] Points and coupon system
- [ ] Business website API

## Local / server setup

See [`app/README.md`](app/README.md) for full setup instructions.

## Self-hosted on

Debian 12 · Docker · Nginx · MariaDB · Cloudflared tunnel
