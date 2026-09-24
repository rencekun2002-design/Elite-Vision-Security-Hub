# Anchor PC deployment

The anchor PC is the office server. Keep it on the private office network and
use the laptop only for development and pushing source changes.

## First setup

Install XAMPP with Apache, PHP, and MySQL/MariaDB, then install Git. Clone the
repository into the XAMPP web root:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/rencekun2002-design/Elite-Vision-Security-Hub.git Elite-Vision-Security-Hub
```

Start Apache and MySQL in the XAMPP Control Panel. Confirm PHP has `pdo_mysql`
and `pdo_sqlite` enabled.

### Drill simulator database

Create a database named `evs_drills` in phpMyAdmin and import the point-system schema:

```text
C:\xampp\htdocs\Elite-Vision-Security-Hub\evs-drill-simulator.sql
```

For a passworded MySQL account, define these Apache environment variables in
the active Apache configuration before restarting Apache:

```apache
SetEnv EVS_DB_HOST localhost
SetEnv EVS_DB_NAME evs_drills
SetEnv EVS_DB_USER evs_app
SetEnv EVS_DB_PASS replace-with-the-database-password
```

The application defaults to `root` with no password, which is suitable only
for a controlled local installation.

### Payroll system

The payroll system creates `evs-payroll-system\invoices.db` automatically. It
also writes generated PDFs to `pdfs\` and logs to `logs\`. Ensure the Apache
process can write to those folders.

Configure email with Apache environment variables if invoice email is needed:

```apache
SetEnv EVS_SITE_URL http://192.168.1.25/evs-payroll-system/
SetEnv EVS_SMTP_USERNAME your-email@example.com
SetEnv EVS_SMTP_PASSWORD replace-with-a-new-app-password
SetEnv EVS_SMTP_FROM_EMAIL your-email@example.com
SetEnv EVS_SMTP_FROM_NAME Elite Vision Security
```

Replace the example LAN address with the anchor PC's actual address. Never put
the SMTP password in Git.

## Routine update

On the laptop:

```powershell
git add .
git commit -m "Describe the change"
git push origin main
```

On the anchor PC, back up runtime data first, then pull:

```powershell
cd C:\xampp\htdocs\Elite-Vision-Security-Hub
copy evs-payroll-system\invoices.db backups\invoices-%date:~-4,4%%date:~4,2%%date:~7,2%.db
git pull --ff-only origin main
```

The database, logs, and PDFs are ignored by Git and remain on the anchor PC.
Do not use `git reset --hard` on this server unless you have confirmed the
runtime backup exists.

## Access from office PCs

Run `ipconfig` on the anchor PC and use its IPv4 address from other machines:

```text
http://ANCHOR-IP/Elite-Vision-Security-Hub/
http://ANCHOR-IP/evs-payroll-system/
```

Allow Apache through Windows Firewall only on the Private network profile.
Both applications currently lack user authentication, so do not expose port
80 to the public internet.

## Rollback

If a code update breaks the applications, identify the last known-good commit:

```powershell
git log --oneline -5
git reset --hard COMMIT_ID
```

Restore the payroll SQLite backup only if the database itself was damaged.
Code rollback and database restore are separate operations.