# Git-based deployment

This application requires Apache/PHP and MySQL. It cannot be deployed as a static site.

## 1. Create the repository

Create an empty repository on GitHub, GitLab, or another Git server, then from this
project directory run:

```powershell
git init
git add .
git commit -m "Initial EVS drill simulator"
git branch -M main
git remote add origin https://github.com/ACCOUNT/REPOSITORY.git
git push -u origin main
```

Replace the remote URL with the repository you actually use. Do not commit `.env`,
passwords, or production database exports.

## 2. Deploy to an Apache/PHP host

Install Git on the host, enable Apache PHP and PDO MySQL, then clone the repository
into the web root. For XAMPP on Windows:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/ACCOUNT/REPOSITORY.git evs-drill-simulator
```

For later releases, update the deployed working tree:

```powershell
cd C:\xampp\htdocs\evs-drill-simulator
git pull --ff-only origin main
```

Using `--ff-only` prevents the web server checkout from creating an unexpected merge
commit. Keep the deployment checkout on the `main` branch.

## 3. Configure the database

1. Start Apache and MySQL.
2. Create the `evs_drills` database in phpMyAdmin.
3. Import `database2.sql` once to create the schema and seed data.
4. Set `EVS_DB_HOST`, `EVS_DB_NAME`, `EVS_DB_USER`, and `EVS_DB_PASS` in the Apache/PHP
   environment. The application keeps the local XAMPP defaults when these variables
   are not set.
5. Change the `ADMIN_CODE` value in `mainpage.html` before production deployment.

The API reads these environment variables in `api/db.php`; credentials do not need to
be stored in Git.

## 4. Verify a deployment

Open:

```text
http://localhost/evs-drill-simulator/
```

Then run a drill and confirm that the result appears in Drill History. If the page
loads but data requests fail, check Apache's PHP error log, MySQL availability, and
the four `EVS_DB_*` values.

## Rollback

On the host, identify the last known-good commit and reset only the deployment
checkout to it:

```powershell
git log --oneline -5
git reset --hard COMMIT_ID
```

Do not reset the production database as part of an application-code rollback.
