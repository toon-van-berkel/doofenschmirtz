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
    ├── login.html
    ├── js/
    ├── api/
    │   ├── .htaccess
    │   └── index.php
    └── _backend/
        ├── .htaccess
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
```

`htdocs/api/index.php` acts as the public API entry point and forwards requests to the main backend front controller.

The source backend remains under:

```text
htdocs/_backend/
```

Direct web access to this directory is blocked using `.htaccess`.

## Database configuration

`backend/config/database.php` is developer-specific and is not included in the generated deployment package.

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