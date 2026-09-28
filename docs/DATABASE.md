# Database

The project uses a local MySQL or MariaDB database during development.

## Setup

1. Create the database:

```sql
CREATE DATABASE doofenshmirtz;
```

2. Import [database/schema.sql](../database/schema.sql).
3. Copy `.env.example` to `.env.production` for a deployment build, or create
   `backend/.env` for local backend development.
4. Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and `APP_ENV` in that
   environment file.

`.env.example` is the shared template. Local `backend/.env` and production
`.env.production` files contain environment-specific credentials and are
ignored by Git. Do not commit real credentials or production data.

## Current schema

`database/schema.sql` currently defines one table:

```text
users
├── id             unsigned auto-increment primary key
├── username       VARCHAR(50), required and unique
├── email          VARCHAR(255), required and unique
├── password_hash  VARCHAR(255), required
└── created_at     timestamp, defaults to the current time
```

The authentication implementation uses this table for registration and login.

## Schema changes

When the schema changes, test it locally and update `database/schema.sql` in the same change. Do not add production dumps, real user data, or credentials.

