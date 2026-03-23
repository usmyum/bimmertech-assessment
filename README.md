# BimmerTech Firmware Manager

A Symfony 8 application for managing and serving CarPlay / Android Auto MMI firmware download links. Customers enter their device's software and hardware version to receive the correct firmware update link. Staff manage firmware versions through an EasyAdmin panel — no code changes required when new firmware is released.

---

## Tech Stack

| Technology | Version | Why |
|---|---|---|
| **PHP** | 8.5+ | Required by Symfony 8 |
| **Symfony** | 8.0 | Application framework — routing, controllers, templating |
| **Doctrine ORM** | 3.6 | Database abstraction — maps PHP objects to MySQL tables |
| **Doctrine Migrations** | 4.0 | Tracks and runs schema changes safely |
| **EasyAdmin** | 5.0 | Auto-generates the admin CRUD panel from entity definitions |
| **MySQL** | 8.3 | Stores all firmware version records |
| **Caddy** | (via Docker) | Web server — bundled in the Symfony Docker skeleton |
| **Docker Compose** | — | Runs PHP + MySQL + Caddy as a single stack locally |

---

## Project Structure

```
src/
├── Controller/
│   ├── FirmwareController.php              # Customer page (GET /) + API (POST /api/...)
│   └── Admin/
│       ├── DashboardController.php         # EasyAdmin dashboard at /admin
│       └── SoftwareVersionCrudController.php  # CRUD for firmware versions
├── Entity/
│   └── SoftwareVersion.php                 # Doctrine entity — maps to software_version table
└── Repository/
    └── SoftwareVersionRepository.php       # Custom DB queries

templates/
├── firmware/
│   └── index.html.twig                     # Customer-facing download page
└── admin/
    └── dashboard.html.twig                 # Admin dashboard homepage

migrations/
└── Version20240101000000.php               # Creates the software_version table

seed.sql                                    # Populates the DB with all existing firmware versions
docker-compose.yml                          # Docker stack definition
```

---

## Quick Start

### 1. Clone and start Docker

```bash
docker compose up -d
```

### 2. Install PHP dependencies

```bash
docker compose exec php composer install
```

### 3. Run the database migration

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

Type `yes` when prompted. This creates the `software_version` table.

### 4. Seed the database

```bash
docker compose exec -T database mysql -u root -proot app < seed.sql
```

### 5. Clear cache

```bash
docker compose exec php php bin/console cache:clear
```

### 6. Open in browser

```
https://localhost        → Customer download page
https://localhost/admin  → Admin panel
```

---

## Configuration

### Changing ports

Open `docker-compose.yml` and edit the `ports` section under the `php` service:

```yaml
ports:
  - "80:80"     # Change left side — e.g. "8080:80" to use https://localhost:8080
```

And under the `database` service:

```yaml
ports:
  - "3307:3306" # Change left side — e.g. "3308:3306"
```

Then restart:

```bash
docker compose down && docker compose up -d
```

### Changing database credentials

Edit the `database` service in `docker-compose.yml`:

```yaml
database:
  environment:
    MYSQL_ROOT_PASSWORD: root      # ← change this
    MYSQL_DATABASE: app            # ← change this
```

And update `DATABASE_URL` under the `php` service to match:

```yaml
php:
  environment:
    DATABASE_URL: "mysql://root:root@database:3306/app?serverVersion=8.3&charset=utf8mb4"
#                         ^^^^  ^^^^              ^^^
#                         user  pass              db name
```

Alternatively, set `DATABASE_URL` in your `.env.local` file (overrides everything else):

```env
DATABASE_URL="mysql://root:yournewpassword@database:3306/app?serverVersion=8.3&charset=utf8mb4"
```

### Switching HTTP / HTTPS

The app uses Caddy as the web server. To force plain HTTP, set `SERVER_NAME` in `docker-compose.yml`:

```yaml
php:
  environment:
    SERVER_NAME: "localhost:80"
    DEFAULT_URI: https://localhost
```

To enable HTTPS for a real domain (Caddy auto-provisions Let's Encrypt certificates):

```yaml
php:
  environment:
    SERVER_NAME: "yourdomain.com"
```

### Changing the app environment

In `.env`:

```env
APP_ENV=dev     # dev = debug toolbar, detailed errors
APP_ENV=prod    # prod = cached, no debug output
```

After switching to `prod`:

```bash
docker compose exec php php bin/console cache:clear
```

---

## Database Access

### Terminal

```bash
docker compose exec database mysql -u root -proot app
```

### GUI tool (TablePlus, DBeaver, DataGrip, etc.)

```
Host:      127.0.0.1
Port:      3307
User:      root
Password:  root
Database:  app
```

---

## Admin Panel

Go to `https://localhost/admin` to manage firmware versions.

**To add a new firmware version:**

1. Click **Software Versions → Add New Version**
2. Fill in the fields (see table below)
3. Click **Save** — live immediately

**When a new firmware release comes out:**

1. Find the version currently marked **Is Latest = Yes**, edit it, uncheck **Is Latest**, and add its download links
2. Add the new version with **Is Latest** checked and download links empty

> ⚠️ Changes are live immediately. Incorrect firmware links can permanently damage a customer's device.

### Field Reference

| Field | Required | Example | Notes |
|---|---|---|---|
| Product Name | ✅ | `MMI Prime CIC` | Must start with `LCI ` for LCI hardware variants |
| Full Version String | ✅ | `v3.3.7.mmipri.c` | With leading `v` |
| Customer Version String | ✅ | `3.3.7.mmipri.c` | Without leading `v` — what customers type in |
| Is Latest? | ✅ | On/Off | Mark only one version per product as latest |
| General Download Link | ➖ | Google Drive URL | Full package link, empty for LCI and latest versions |
| ST Hardware Link | ➖ | Google Drive URL | For ST/CIC hardware |
| GD Hardware Link | ➖ | Google Drive URL | For GD/NBT/EVO hardware |

---

## API

The customer page calls this endpoint internally.

```
POST /api/carplay/software/version
Content-Type: application/x-www-form-urlencoded

version=3.3.6.mmipri.c&hwVersion=CPAA_2022.01.01
```

**Response — update available:**
```json
{
  "versionExist": true,
  "msg": "The latest version of software is v3.3.7 ",
  "link": "https://drive.google.com/...",
  "st": "https://drive.google.com/...",
  "gd": ""
}
```

**Response — already up to date:**
```json
{
  "versionExist": true,
  "msg": "Your system is upto date!",
  "link": "", "st": "", "gd": ""
}
```

**Response — unrecognised hardware:**
```json
{
  "msg": "There was a problem identifying your software. Contact us for help."
}
```

### Hardware version patterns

| HW Version pattern | Hardware type | Link returned |
|---|---|---|
| `CPAA_YYYY.MM.DD` | Standard ST | ST link |
| `CPAA_G_YYYY.MM.DD` | Standard GD | GD link |
| `B_C_YYYY.MM.DD` | LCI CIC | ST link |
| `B_N_G_YYYY.MM.DD` | LCI NBT | GD link |
| `B_E_G_YYYY.MM.DD` | LCI EVO | GD link |

---

## Useful Commands

```bash
# Start the stack
docker compose up -d

# Stop the stack
docker compose down

# Restart just PHP (after code changes)
docker compose restart php

# Run a migration
docker compose exec php php bin/console doctrine:migrations:migrate

# Generate a new migration after entity changes
docker compose exec php php bin/console doctrine:migrations:diff

# Clear cache
docker compose exec php php bin/console cache:clear

# List all routes
docker compose exec php php bin/console debug:router

# Open a shell inside the PHP container
docker compose exec php bash
```
