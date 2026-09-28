# Deploy Instructions

The deployment scripts create a fresh, disposable `deploy/` directory from the project source files.

## Generate the output

Windows:

```powershell
.\scripts\build-deploy.ps1
```

macOS/Linux:

```bash
chmod +x scripts/build-deploy.sh
./scripts/build-deploy.sh
```

Do not edit files inside `deploy/`. Make changes in the source directories and regenerate the deployment output.

## Generated structure

The scripts create the following hosting structure:

```text
deploy/
└── htdocs/
    ├── index.html
    ├── login.html
    ├── js/
    ├── api/
    │   ├── .htaccess
    │   └── index.php
    └── _backend/
        ├── .htaccess
        ├── .env
        ├── config/
        ├── public/
        └── src/
```

## Hosting layout

The contents of:

```text
deploy/htdocs/
```

should be uploaded to the website's `htdocs/` directory on InfinityFree.

For the current domain, InfinityFree uses:

```text
doofenschmirtz.b0i.eu/htdocs/
```

## Frontend

Frontend files are copied directly into `htdocs/`.

Example:

```text
https://doofenschmirtz.b0i.eu/login.html
```

## Backend API

Production API requests use the `/api` path.

Examples:

```text
POST /api/auth/register
POST /api/auth/login
GET /api/auth/me
POST /api/auth/logout
GET /api/health
```

`htdocs/api/index.php` acts as the public API entry point and forwards requests to the main backend front controller.

The source backend remains under:

```text
htdocs/_backend/
```

Direct web access to this directory is blocked using `.htaccess`.

## Database configuration

The build script reads the gitignored root file `.env.production` when it
exists and copies it to `deploy/htdocs/_backend/.env`.

Use `.env.example` as the template and fill in:

```text
DB_HOST=
DB_NAME=
DB_USER=
DB_PASSWORD=
APP_ENV=production
```

If `.env.production` is missing, the build prints a warning and the production
environment file must be added manually to `_backend/.env` before upload.

Production requires its own database configuration using the credentials provided by the hosting environment.

Do not upload local database credentials.

## Important

`deploy/` is generated output and can be deleted and recreated at any time.

Always make changes in:

```text
frontend/
backend/
```

and then run the deployment script again.
