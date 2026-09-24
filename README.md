# EVS Command Center Hub — XAMPP / MySQL Edition

This repository stores everything (clients, scenarios, operators, executives, scenario history)
in a real MySQL database through a small PHP API, so you get full **Create, Read, Update,
Delete** on every record instead of browser-only storage.

## 1. Install / start XAMPP

Download XAMPP if you don't have it, then open the **XAMPP Control Panel** and start:

- **Apache**
- **MySQL**

## 2. Clone this repository into htdocs

Clone the repository into XAMPP's `htdocs` directory:

- Windows: `C:\xampp\htdocs\Elite-Vision-Security-Hub`
- macOS: `/Applications/XAMPP/htdocs/evs-drill-simulator`
- Linux: `/opt/lampp/htdocs/evs-drill-simulator`

## 3. Create the database

1. Open `http://localhost/phpmyadmin` in your browser.
2. Click **Import** in the top menu.
3. Choose `evs-drill-simulator.sql` from the repository root.
4. Click **Go**.

This creates the `evs_drills` database with every table and loads all the existing
clients, scenarios, operators, and executives so the app works immediately.

## 4. Check the database credentials (only if needed)

By default XAMPP's MySQL uses the user `root` with **no password**, which is already
set in `api/db.php`. If your setup uses a different user or password, open
`api/db.php` and update these two lines:

```php
$DB_USER = 'root';
$DB_PASS = '';
```

## 5. Open the app

Go to:

```
http://localhost/Elite-Vision-Security-Hub/
```

Do **not** open `evs-drill-entry.html` by double-clicking it — the page needs to be served by
Apache so it can talk to the PHP API. Opening it directly from your file system will
show a "can't reach the database" message.

## What's in each folder

- `evs-drill-simulator.html` — the point-based application (drill runner, directory, add/edit forms).
- `evs-drill-simulator.sql` — schema + seed data for the point-based application, import once in phpMyAdmin.
- `api/db.php` — shared database connection settings.
- `api/clients.php` — CRUD for clients (create, read, update, delete).
- `api/incidents.php` — CRUD for drills/incidents, including the two-stage complex drills.
- `api/operators.php` — CRUD for the operator/supervisor roster.
- `api/executives.php` — CRUD for the CEO roster.
- `api/history.php` — reads and clears drill history.

## Managing your data

The app now has two modes:

- **Client mode** (default) — run drills, view Statistics (leaderboard,
  achievement badges, stats), view your Drill History (filterable by
  green/orange/red severity), and browse the Directory read-only.
- **Admin mode** — click **Admin Login** in the header and enter the access
  code (default `evsadmin`, set in `evs-drill-simulator.html` via the `ADMIN_CODE`
  constant near the top of the `<script>` block — change this before
  deploying). Admin mode unlocks:
  - Add/Edit/Delete on Team, Clients, and Drills in the Directory
  - The History tab becomes **Review**, adding a delete button per
    submission and a "Clear all" button

This is a lightweight client-side gate, not real authentication — anyone
who knows the code (or reads the page source) can enable admin mode, so
don't rely on it to protect sensitive data. For real access control, add
a login check in the PHP API layer.

### Scoring

Each submitted drill is graded on four equally-weighted components:

- **Classification** — did the operator pick the right severity category?
- **Tool Selection** — did they use the ideal response tools for the incident?
- **Script Completeness** — did their call scripts hit the required talking
  points? (If a voicemail was recorded for a tool instead of typing the
  script, that tool is graded as complete — you don't have to do both.)
- **Response Time** — did they finish within the target time for the
  incident's severity (90s for red, 150s for orange, 240s for green)?

### Spaced repetition

When picking a random incident for **New Drill**, the app looks at the
selected operator's past scores by category and weights the pick toward
categories they've historically scored lower on, so practice time
concentrates on real weak spots instead of being purely random.

Voicemail recording (the microphone button for speaker/siren/police/supervisor/CEO
calls) stays entirely in the browser and does not require the database — it works the
same as before.
# Elite Vision Security Hub

This repository contains two PHP systems intended for use on an office LAN:

- `evs-drill-simulator/` - drill simulator backed by MySQL/MariaDB.
- `evs-payroll-system/` - invoice and payroll system backed by SQLite.

## Recommended workflow

Use the laptop for development and GitHub for source control. Designate one
office PC as the anchor server. That PC runs XAMPP/Apache and serves both
applications to the office network.

```text
Laptop -> git push -> GitHub <- git pull <- Anchor office PC
												  |
												  +-- Apache/PHP serves both systems
												  +-- MySQL stores drill data
												  +-- SQLite stores payroll data
```

The applications are available from the anchor PC at:

```text
http://localhost/evs-drill-simulator/
http://localhost/evs-payroll-system/
```

Other office PCs use the anchor PC's LAN address, for example:

```text
http://192.168.1.25/evs-drill-simulator/
http://192.168.1.25/evs-payroll-system/
```

## Initial anchor-PC setup

1. Install XAMPP with Apache, PHP, and MySQL/MariaDB on the anchor PC.
2. Install Git and clone this repository into `C:\xampp\htdocs`:

	```powershell
	cd C:\xampp\htdocs
	git clone https://github.com/rencekun2002-design/Elite-Vision-Security-Hub.git Elite-Vision-Security-Hub
	```

3. Start Apache and MySQL from the XAMPP Control Panel.
4. Import `evs-drill-simulator\database2.sql` into a database named `evs_drills`.
5. Allow inbound TCP port 80 through Windows Firewall on the private office network.
6. Find the anchor PC's LAN address with `ipconfig` and test both URLs from another office PC.

Detailed update and backup instructions are in [ANCHOR-PC-DEPLOYMENT.md](ANCHOR-PC-DEPLOYMENT.md).

## Updating from the laptop

Commit and push code from the laptop:

```powershell
git add .
git commit -m "Describe the change"
git push origin main
```

Then update the anchor PC from its repository directory:

```powershell
cd C:\xampp\htdocs\Elite-Vision-Security-Hub
git pull --ff-only origin main
```

Do not commit `.env` files, `invoices.db`, logs, PDFs, or production credentials.
