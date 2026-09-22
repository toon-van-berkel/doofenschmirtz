# Development

## Project structure

```text
doofenschmirtz/
├── frontend/
│   ├── login.html
│   └── js/api/
├── backend/
│   ├── config/
│   │   ├── database.php
│   │   ├── database.example.php
│   │   └── routes.php
│   ├── public/
│   │   ├── .htaccess
│   │   └── index.php
│   └── src/
│       ├── Controllers/
│       │   └── AuthController.php
│       ├── Services/
│       │   └── AuthService.php
│       ├── Database.php
│       └── Router.php
├── database/
├── scripts/
└── docs/
```

## Backend responsibilities

- `public/index.php` is the backend entry point. It starts the session,
  configures CORS and session behavior, loads dependencies, and dispatches the
  router.
- `Router.php` maps HTTP method and path combinations to controller actions.
- `AuthController.php` handles request input, validation, and JSON responses.
- `AuthService.php` handles user lookup, password hashing, password
  verification, user creation, and session login logic.
- `Database.php` provides the shared PDO database connection.
- `routes.php` registers implemented backend routes and maps them to controller
  actions.

## Local URLs

Run the frontend with a local static server such as VS Code Live Server:

```text
Frontend: http://127.0.0.1:5500
Backend:  https://doofenschmirtz.test
```

Laravel Herd uses `backend/public/` as the backend web root. The backend entry
point is `backend/public/index.php`.

## Authentication API

The currently supported endpoints are:

```text
POST /auth/register
POST /auth/login
```

The frontend sends these requests through `frontend/js/api/client.js`. Feature-
specific authentication calls are defined in `frontend/js/api/auth.js`, and are
exported through `frontend/js/api/index.js`.

## Database

Each developer uses a local MySQL or MariaDB database. Copy
`backend/config/database.example.php` to `backend/config/database.php` and set
the local credentials. The latter file is developer-specific and ignored by
Git.

See [DATABASE.md](DATABASE.md) for the schema setup.

## Workflow

1. Create a branch.
2. Start the local database.
3. Start the frontend static server.
4. Verify the Herd backend is available.
5. Test changes locally.
6. Commit and open a pull request.
