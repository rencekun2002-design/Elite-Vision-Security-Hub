# EVS Drill Simulator - Project Development Summary

## 1. Project Overview

The EVS Drill Simulator is a browser-based internal application for running emergency-response drills, recording results, managing staff and incidents, and reviewing performance.

The application is designed to run on an Apache/PHP/MySQL server, such as XAMPP on Windows.

## 2. Main Features

- Runs emergency-response drills.
- Supports green, orange, and red severity levels.
- Scores classification, tool selection, script completeness, and response time.
- Stores drill history in MySQL.
- Provides statistics, leaderboards, badges, and history filtering.
- Selects practice drills using spaced repetition based on weaker performance areas.
- Supports browser-based voicemail recording.
- Manages clients, incidents, operators, executives, and drill history.
- Provides PHP API endpoints for database operations.

## 3. Technology Stack

- Frontend: HTML, CSS, and JavaScript.
- Backend: PHP.
- Database: MySQL.
- Local server: XAMPP Apache and MySQL.
- Database setup: `evs-drill-simulator.sql`.
- Browser entry point: `evs-drill-entry.html`, which redirects to `evs-drill-simulator.html`.

The application must be served through Apache. It should not be opened by double-clicking the HTML file because the PHP APIs require a web server.

Local URL:

```text
http://localhost/evs-drill-simulator/
```

## 4. Database and API

The database is created by importing `evs-drill-simulator.sql` into MySQL through phpMyAdmin.

Important API files include:

- `api/db.php` - shared database connection.
- `api/clients.php` - client CRUD operations.
- `api/incidents.php` - incident and drill CRUD operations.
- `api/operators.php` - operator and supervisor management.
- `api/executives.php` - executive management.
- `api/history.php` - drill history operations.
- `api/documents.php` - document-related API operations.

The `drill_history.detail_json` column stores the classification, tool, script, and
response-time breakdown for each new submission. Existing databases must run
`migrations/001_add_history_detail.sql` once.

Client police department and hotline fields are added by
`migrations/002_add_police_hotline.sql`. The PDF-sourced client addresses, response
equipment, departments, and hotlines are imported by
`migrations/003_import_client_hotlines.sql`.

The database connection supports environment variables:

- `EVS_DB_HOST`
- `EVS_DB_NAME`
- `EVS_DB_USER`
- `EVS_DB_PASS`

If these variables are not set, local XAMPP defaults are used: host `localhost`, database `evs_drills`, user `root`, and an empty password.

## 5. User Access

Users access the system through a web browser. They do not need Visual Studio Code, Git, or XAMPP installed on their own computers.

For office-only use, the anchor PC can host the application. Other office computers can open a URL such as:

```text
http://192.168.1.25/evs-drill-simulator/
```

The anchor PC must remain powered on, with Apache and MySQL running. Windows Firewall must allow Apache connections on the private office network.

## 6. Current Access and Permission Model

The application currently has two modes:

- Client mode for running drills, viewing statistics, viewing history, and browsing the directory.
- Admin mode for managing teams, clients, drills, and history.

The admin mode currently uses a client-side access code in `evs-drill-simulator.html`. This is not real security because the code can be discovered in the page source. It should not be used to protect sensitive production data.

## 7. Git-Based Deployment Work Completed

The following deployment support was added:

- `DEPLOYMENT.md` documents GitHub setup, server cloning, updates, verification, and rollback.
- `.gitignore` excludes secrets, local archives, logs, IDE files, and operating-system files.
- `.env.example` documents the database environment variables without containing real credentials.
- `api/db.php` reads production database credentials from environment variables.
- Documentation references were corrected to use `evs-drill-simulator.sql`.

The intended update command on the deployment server is:

```powershell
cd C:\xampp\htdocs\evs-drill-simulator
git pull --ff-only origin main
```

The `--ff-only` option prevents the deployment checkout from creating unexpected merge commits.

## 8. Initial Git Setup

After installing Git and creating a private GitHub repository, run these commands from the project directory:

```powershell
git init
git add .
git commit -m "Initial EVS drill simulator"
git branch -M main
git remote add origin https://github.com/ACCOUNT/REPOSITORY.git
git push -u origin main
```

Replace the example remote URL with the actual repository URL. Do not commit passwords, `.env` files, or production database exports.

## 9. Deployment Process

1. Install Git on the anchor PC.
2. Install and configure XAMPP.
3. Start Apache and MySQL.
4. Create the `evs_drills` database.
5. Import `evs-drill-simulator.sql` once through phpMyAdmin.
6. If upgrading an existing database, run `migrations/001_add_history_detail.sql` once.
7. Clone the Git repository into `C:\xampp\htdocs`.
8. Configure the `EVS_DB_*` environment variables.
9. Change the default admin code before production use.
10. Open the application through the local URL.
11. Run a test drill and confirm that the result appears in Drill History with a full breakdown.
12. Test access from another office computer.

## 10. Important Gaps and Risks

### Authentication

The application does not yet have real user accounts, password authentication, or secure server-side role enforcement.

### Database security

The default XAMPP configuration uses MySQL `root` with no password. Production should use a dedicated database account with a strong password and limited permissions.

### Network security

The application should be restricted to the office private network. It should not be exposed directly to the public internet from a standard XAMPP installation.

### HTTPS

HTTPS should be used if users access the system remotely or if sensitive information is stored.

### Backups

The MySQL database needs scheduled backups stored separately from the anchor PC. Restoring a backup should be tested periodically.

### Automated testing

There are currently no automated application tests. API requests, invalid input, permission checks, database failures, and drill scoring should be tested.

### Git status

The repository-side deployment files are prepared, but Git must be installed and the project must be initialized and pushed to the chosen remote repository.

## 11. Recommended Future Development

### Phase 1: Complete Basic Deployment

- Install Git on the anchor PC.
- Create a private GitHub repository.
- Push the project to GitHub.
- Clone it into the XAMPP web root.
- Import the database.
- Test access from multiple office computers.

### Phase 2: Add Security

- Add PHP login and logout.
- Store passwords using secure password hashes.
- Use server-side sessions.
- Add administrator, supervisor, and operator roles.
- Enforce permissions inside the PHP API.
- Remove dependence on the frontend admin code.
- Replace `Access-Control-Allow-Origin: *` with a restricted origin policy.
- Add HTTPS where appropriate.

### Phase 3: Improve Reliability

- Schedule automated database backups.
- Add a health-check endpoint.
- Improve error logging.
- Document recovery procedures.
- Create a staging installation for testing updates.
- Use Git tags for stable releases.
- Keep rollback procedures documented and tested.

### Phase 4: Improve the User Experience

- Add onboarding instructions for new operators.
- Add date-range and operator filters.
- Add exportable reports.
- Add printable or PDF performance reports.
- Continue improving tablet and mobile layouts.
- Optionally package the site as a Progressive Web App so it behaves more like an installed application.

## 12. Recommended Target Architecture

For a small office:

```text
Users' browsers
        |
Office network
        |
Anchor PC or internal server
        |
Apache + PHP
        |
MySQL database
```

For a larger organization or remote access, move the application to a managed PHP/MySQL server with HTTPS, authentication, automated backups, and restricted administrative access.

## 13. Deployment Checklist

- [ ] Git installed on the development PC.
- [ ] Git installed on the anchor PC.
- [ ] Private remote repository created.
- [ ] Initial commit pushed.
- [ ] `.env` and passwords excluded from Git.
- [ ] Apache running.
- [ ] MySQL running.
- [ ] `evs-drill-simulator.sql` imported.
- [ ] Production database account configured.
- [ ] Application tested locally.
- [ ] Application tested from another office computer.
- [ ] Windows Firewall configured for the private network.
- [ ] Database backup process created.
- [ ] Authentication added before sensitive production use.
