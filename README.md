# Dhaa Baja — Ancestral Rhythms (Fullstack PHP + MySQL)

A complete fullstack rebuild of the Dhaa Baja design: a public site (Home, Library,
Events, About, Login/Signup, Search) backed by a real MySQL database, plus a full
admin dashboard for managing rhythms, categories, events, and users — including
per-user access control, clean extension-less URLs, and a deployable path to
**Vercel** in addition to traditional Apache/PHP hosting.

## Stack
- **Backend:** PHP 8+ (plain PHP, PDO, no framework)
- **Database:** MySQL / MariaDB
- **Frontend:** Tailwind CSS (CDN) — separated `assets/css` and `assets/js` files
- **Sessions:** database-backed (see below) — works correctly on both traditional
  hosting and serverless platforms
- **File storage:** uploaded sheet-music files are stored as BLOBs in the database,
  not on local disk — see below for why

Every page and workflow (auth, CRUD, search, access control, file upload/download,
sessions) has been tested end-to-end against a live MySQL instance.

---

## Deploying to Vercel

Vercel doesn't run PHP or host MySQL natively, so getting this stack running there
took a few real architectural changes — not just config. Here's what changed and why:

### 1. A single serverless function, not one per page
Vercel's PHP support comes from a community runtime
([vercel-community/php](https://github.com/vercel-community/php)), and the Hobby
plan caps a deployment at **12 functions**. This app has 25+ pages, so instead of
turning every `.php` file into its own function, **`api/index.php`** is the *only*
declared function — a tiny front controller that reads the clean URL
(`/library`, `/admin/rhythms`, etc.) and `require`s the matching real file
(`library.php`, `admin/rhythms.php`, ...) unmodified. `vercel.json` routes every
request through it, except `/assets/*`, which Vercel serves directly as static files.

### 2. Database-backed sessions
PHP's default session handler writes files to local disk. On Vercel, each request
can land on a different, short-lived instance with its own throwaway filesystem —
a session file written during login might already be gone by the next request,
silently breaking login and CSRF. **`includes/db_session_handler.php`** stores
session data as a row in a `sessions` table instead, so every instance reads/writes
the same source of truth. This is wired in automatically in `includes/auth.php` and
works identically on a normal single-server host too.

### 3. Sheet music stored in the database, not on disk
Vercel's filesystem is read-only/ephemeral in production — a file saved by
`move_uploaded_file()` would vanish (or fail to save at all) by the next request.
Uploaded sheet-music files are now stored as a `LONGBLOB` column on the `rhythms`
table instead, streamed back out by `api/download.php` (public) and
`admin/view_sheet.php` (admin preview). No third-party storage service required.

### 4. An external MySQL-compatible database
Vercel doesn't host MySQL. Pick a provider and get a connection string:
- [PlanetScale](https://planetscale.com) or [TiDB Cloud](https://tidbcloud.com) — MySQL-compatible, generous free tiers
- [Aiven](https://aiven.io) or [Railway](https://railway.app) — plain MySQL

Import `database.sql` into it (most providers give you a `mysql` CLI connection
string or a web SQL console — either works).

### Deployment steps
1. Create the database with your chosen provider and import `database.sql`.
2. In the Vercel dashboard, set these **Environment Variables** on the project:
   | Variable | Value |
   |---|---|
   | `DB_HOST` | your provider's host |
   | `DB_PORT` | usually `3306` |
   | `DB_NAME` | `dhaa_baja` |
   | `DB_USER` | your provider's username |
   | `DB_PASS` | your provider's password |
   | `DB_SSL` | `true` (most hosted MySQL providers require an encrypted connection) |
3. Push this project to a Git repo and import it in Vercel, or run `vercel` from
   the project root with the Vercel CLI. `vercel.json` handles the rest.
4. Visit your `*.vercel.app` domain.

### Known limitations on Vercel
- **Cold starts**: the community PHP runtime spins up a fresh PHP process per
  invocation; expect a slower first request after idle periods.
- **maxDuration**: set to 30s in `vercel.json`; large file uploads on a slow
  connection could theoretically hit this — raise it if needed (Pro/Enterprise
  plans allow longer durations).
- This has been thoroughly tested locally against the same PHP/MySQL logic
  Vercel's runtime executes, but actual behavior on Vercel's infrastructure
  (header propagation, cold-start timing, etc.) hasn't been verified against a
  live Vercel deployment from this environment — test your first deploy before
  relying on it.

### Troubleshooting: "Database connection failed: SQLSTATE[HY000] [2002] No such file or directory"
This means PHP tried to reach MySQL over a local Unix socket file, which doesn't
exist on Vercel (there's no local MySQL there). It almost always means the
`DB_HOST` environment variable isn't set in **Project Settings > Environment
Variables**, or was set to `localhost` — go set it to your hosted MySQL
provider's actual hostname (e.g. PlanetScale's `aws.connect.psdb.cloud`), along
with `DB_NAME`, `DB_USER`, `DB_PASS`, and `DB_SSL=true`, then redeploy. The app
now also auto-corrects a literal `localhost` value to force a TCP connection, so
this specific error shouldn't recur even if that value slips through — if you
still see it, it means the variables are missing entirely rather than just
misconfigured.

---

## Clean URLs (no `.php` in the address bar)
Every page is reachable without its `.php` extension — `/library` instead of
`/library.php`, `/admin/rhythms` instead of `/admin/rhythms.php`.

- **Vercel**: handled by `vercel.json` + `api/index.php` (see above).
- **Apache**: the included **`.htaccess`** handles this via `mod_rewrite`. Make
  sure `AllowOverride All` is set and `mod_rewrite` is enabled. Old `.php` links
  get a permanent 301 redirect to the clean URL.
- **PHP's built-in dev server** doesn't read `.htaccess`, so **`router.php`** is
  included purely for local development:
  ```bash
  php -S localhost:8000 router.php
  ```
  (Neither Vercel nor Apache use this file — it's dev-only.)
- **Nginx**: not included by default; the equivalent is:
  ```nginx
  location / {
      try_files $uri $uri.php $uri/ =404;
  }
  ```

## Folder structure
```
dhaa-baja/
├── vercel.json                 # Vercel routing + function config
├── .vercelignore                # excludes dev-only files from the Vercel deploy
├── .htaccess                     # Apache clean-URL rewrite rules
├── router.php                     # Clean-URL router for `php -S` local dev only
├── database.sql                    # Full schema + seed data — import this first
├── index.php                        # Home (full-bleed hero, featured rhythms, upcoming events)
├── library.php                       # Rhythm library — search, category filter, downloads
├── events.php                         # Events journal — search, category filter (blog-style)
├── about.php                           # About / mission / craftsmanship (dynamic stats)
├── search.php                           # Global search across rhythms, categories & events
├── login.php / signup.php / logout.php
├── includes/
│   ├── config.php                        # DB credentials — reads env vars first, local fallback
│   ├── auth.php                           # sessions, CSRF, visibility/access-control helpers
│   ├── db_session_handler.php              # database-backed PHP session storage
│   ├── head.php / nav.php / footer.php
├── assets/
│   ├── css/site.css, admin.css              # separated stylesheets
│   └── js/site.js, admin.js                  # separated scripts
├── api/
│   ├── index.php                              # Vercel front-controller (the one serverless function)
│   ├── newsletter.php                          # newsletter signup handler
│   ├── download.php                             # sheet-music download (streams from DB, access-controlled)
│   └── rsvp.php                                  # event RSVP handler
└── admin/
    ├── login.php                                  # dedicated admin sign-in
    ├── index.php                                   # dashboard (stats overview) — admin's home page
    ├── rhythms.php / rhythm_form.php                 # rhythm CRUD, BLOB file upload, enable/disable
    ├── view_sheet.php                                 # admin preview of an uploaded sheet-music BLOB
    ├── categories.php / category_form.php               # category CRUD, enable/disable
    ├── events.php / event_form.php                        # event CRUD
    ├── users.php                                           # promote/suspend/delete members
    ├── rsvps.php                                            # confirm/cancel RSVPs
    ├── purchases.php                                         # download log
    ├── subscribers.php / export_subscribers.php               # newsletter list + CSV export
    └── includes/header.php, footer.php                         # shared admin layout/sidebar
```

## Setup (traditional hosting / local dev)

1. **Create the database**
   ```bash
   mysql -u root -p < database.sql
   ```
   Creates the `dhaa_baja` database with all tables (including `sessions`) and
   seed data (4 categories, 5 rhythms, 5 events, 2 users, 2 newsletter subscribers).

2. **Configure the connection**
   Edit `includes/config.php`, or set environment variables `DB_HOST`, `DB_PORT`,
   `DB_NAME`, `DB_USER`, `DB_PASS` (env vars take priority if set):
   ```php
   define('DB_HOST', env('DB_HOST', 'localhost'));
   define('DB_NAME', env('DB_NAME', 'dhaa_baja'));
   define('DB_USER', env('DB_USER', 'root'));
   define('DB_PASS', env('DB_PASS', ''));
   ```

3. **Run it**
   - Local development (clean URLs via the included router):
     ```bash
     php -S localhost:8000 router.php
     ```
     Then visit `http://localhost:8000/`
   - Production (Apache): drop the whole `dhaa-baja/` folder into your Apache + PHP
     web root. `.htaccess` handles clean URLs automatically.
   - Production (Vercel): see the Vercel section above.

## Default logins
| Role  | Email                  | Password      |
|-------|-------------------------|--------------|
| Admin | admin@dhaabaja.com      | Admin@12345  |
| User  | guest@dhaabaja.com      | Guest@12345  |

Admin dashboard lives at **`/admin/login`** (separate from the public `/login`,
though the public login also redirects admins straight to `/admin/`).

## Key features
- **Rhythms & Categories** are fully database-driven and admin-managed, with:
  - Sheet-music file upload (PDF/JPG/PNG/GIF, 10MB max), stored as a database BLOB
  - A master **enable/disable** switch per rhythm and per category
  - **Per-user overrides** — force a specific rhythm or category ON or OFF for one
    particular registered user regardless of its default visibility, enforced both
    in what's shown on the site *and* server-side on the download endpoint itself
- **Search** — a global `/search` page searches rhythms, categories, and events at
  once; `/library` and `/events` also each have their own in-page search. All
  results respect the same per-user visibility rules as everything else.
- **Events** page is a blog-style journal of gatherings.
- **Newsletter** — footer signup form, viewable/exportable as CSV from the admin dashboard.
- **Auth** — bcrypt password hashing, CSRF tokens on every form, database-backed
  sessions for both public users and admins (role stored on the `users` table).

## Security notes for production
- Change the demo passwords immediately.
- Use a dedicated, least-privilege database user rather than `root`.
- Serve over HTTPS and set `session.cookie_secure` in `php.ini` (or rely on
  Vercel's automatic HTTPS).
- On Apache, confirm `mod_rewrite` and `AllowOverride All` are active so
  `.htaccess` is actually honored.
- On Vercel, always set `DB_SSL=true` when connecting to a hosted MySQL provider
  over the public internet.
