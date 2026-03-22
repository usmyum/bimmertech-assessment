# BimmerTech Firmware Manager

CarPlay / Android Auto MMI software update portal with an admin panel for managing firmware versions — built with Symfony 7.1, Doctrine, EasyAdmin 4, and MySQL 8.

---

## Requirements

- Docker + Docker Compose
- No other local dependencies needed — everything runs inside containers

---

## Setup

### 1. Start Docker

```bash
docker compose up -d --build
```

### 2. Install dependencies

```bash
docker compose exec php composer install
```

### 3. Check your database URL

Make sure your `.env` file contains:

```env
DATABASE_URL="mysql://root:root@database:3306/app?serverVersion=8.3&charset=utf8mb4"
```

### 4. Run the migration

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

Type `yes` when prompted.

### 5. Load all firmware versions

```bash
docker compose exec -T database mysql -u root -proot app < seed.sql
```

### 6. Clear the cache

```bash
docker compose exec php php bin/console cache:clear
```

That's it. The app is running with all existing firmware versions loaded.

---

## URLs

| Page | URL |
|---|---|
| Customer download page | `http://localhost/` |
| Admin panel | `http://localhost/admin` |
| API endpoint (POST) | `http://localhost/api/carplay/software/version` |

---

<<<<<<< HEAD
## Important:

You could see a screen saying "your connection is not private" in the browser. You simply can click "Advanced" and then "Proceed to localhost (unsafe)" to bypass this for your local.

=======
>>>>>>> 963ba65 (Featre: BimmerTech firmware manager)
## Admin Panel

Go to `http://localhost/admin` to manage firmware versions. From there you can:

- View all firmware versions in a searchable, filterable table
- Add new firmware versions
- Edit existing versions and download links
- Mark a version as the latest (customers will be told they are up to date — no download link shown)
- Delete old versions

> ⚠️ **Changes take effect immediately for all customers. There is no confirmation step. Always double-check version strings and download links before saving — incorrect firmware can permanently damage a customer's device.**

---

## Adding a New Firmware Version

1. Go to `http://localhost/admin` and click **Software Versions** in the left menu
2. Click **Add New Version** in the top right
3. Fill in all the fields (see Field Reference below)
4. If this is the **latest** version (customers are already up to date), toggle **Is Latest?** on and leave all download links empty
5. Click **Save** — the change is live immediately

### When a new firmware release comes out

You need to do two things:

1. Find the version currently marked **Is Latest = Yes** and edit it. Uncheck **Is Latest?** and add the download links for that version (since it is now an older version customers may need to update from)
2. Add the new version with **Is Latest?** checked and all download links empty

---

## Field Reference

| Field | Required | Example | Notes |
|---|---|---|---|
| Product Name | ✅ | `MMI Prime CIC` | Must start with `LCI ` for LCI hardware variants |
| Full Version String | ✅ | `v3.3.7.mmipri.c` | Include the leading `v` |
| Customer Version String | ✅ | `3.3.7.mmipri.c` | Same as above but WITHOUT the leading `v` — this is what customers type in |
| Is Latest? | ✅ | On / Off | Only one version per product should be marked latest at a time |
| General Download Link | ➖ | Google Drive URL | Full firmware package link. Leave empty for LCI versions and latest versions |
| ST Hardware Link | ➖ | Google Drive URL | Download link for ST (standard/CIC) hardware |
| GD Hardware Link | ➖ | Google Drive URL | Download link for GD (NBT/EVO) hardware |

---

## Database Access

The database runs inside Docker on port `3307` on your host machine.

### Via terminal

```bash
docker compose exec database mysql -u root -proot app
```

### Via a GUI tool (TablePlus, DBeaver, etc.)

```
Host:      127.0.0.1
Port:      3307
User:      root
Password:  root
Database:  app
```

### To re-run the seed file

```bash
docker compose exec -T database mysql -u root -proot app < seed.sql
```

---

## How It Works

When a customer submits their versions, the app:

1. Strips any leading `v` or `V` from the System Version input
2. Analyses the **HW Version** using regex patterns to determine hardware type — if it doesn't match any known pattern, an error is returned
3. Looks up the System Version in the database (case-insensitive)
4. Filters results: standard hardware only matches standard product entries; LCI hardware only matches entries whose Product Name starts with `LCI`, further filtered by CIC / NBT / EVO
5. Returns the correct download link for the customer's hardware type, or tells them they are already up to date

### Hardware version patterns

| Pattern | Hardware type | Link shown |
|---|---|---|
| `CPAA_YYYY.MM.DD` | Standard ST | ST link |
| `CPAA_G_YYYY.MM.DD` | Standard GD | GD link |
| `B_C_YYYY.MM.DD` | LCI CIC | ST link |
| `B_N_G_YYYY.MM.DD` | LCI NBT | GD link |
| `B_E_G_YYYY.MM.DD` | LCI EVO | GD link |

---

## Project Structure

```
src/
├── Controller/
│   ├── FirmwareController.php         # Customer page + API endpoint
│   └── Admin/
│       ├── DashboardController.php    # EasyAdmin dashboard
│       └── SoftwareVersionCrudController.php  # Firmware version CRUD
├── Entity/
│   └── SoftwareVersion.php            # Doctrine entity
└── Repository/
    └── SoftwareVersionRepository.php  # DB queries

templates/
├── firmware/
│   └── index.html.twig                # Customer download page
└── admin/
    └── dashboard.html.twig            # Admin dashboard

migrations/
└── Version20240101000000.php          # Creates software_version table

seed.sql                               # All existing firmware versions
```
