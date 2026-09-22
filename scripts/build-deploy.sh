#!/usr/bin/env bash

set -e

# Builds a disposable hosting package for InfinityFree.
# Source files should always be edited in frontend/ and backend/, never in deploy/.

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

DEPLOY="$ROOT/deploy"
HTDOCS="$DEPLOY/htdocs"
API="$HTDOCS/api"
PRIVATE_BACKEND="$HTDOCS/_backend"

echo "Creating deployment package..."

rm -rf "$DEPLOY"

mkdir -p "$HTDOCS"
mkdir -p "$API"
mkdir -p "$PRIVATE_BACKEND"


# Frontend
cp -R "$ROOT/frontend/." "$HTDOCS/"


# Backend
cp -R "$ROOT/backend/." "$PRIVATE_BACKEND/"


# Never deploy the developer-specific database configuration.
rm -f "$PRIVATE_BACKEND/config/database.php"


# Prevent direct HTTP access to backend source and configuration files.
cat > "$PRIVATE_BACKEND/.htaccess" <<'EOF'
Require all denied
EOF


# Public API entry point.
# Production requests use /api while the backend router expects routes such as
# /auth/login, so the deployment adapter removes the /api prefix.
cat > "$API/index.php" <<'EOF'
<?php

$_SERVER['REQUEST_URI'] =
    preg_replace('#^/api#', '', $_SERVER['REQUEST_URI']) ?: '/';

require_once __DIR__ . '/../_backend/public/index.php';
EOF


# Route all API requests through the deployment entry point.
cat > "$API/.htaccess" <<'EOF'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^ index.php [L,QSA]
EOF


find "$DEPLOY" -type f \( \
    -name ".env" \
    -o -name ".env.local" \
    -o -name ".DS_Store" \
\) -delete


echo ""
echo "Deployment package ready:"
echo "$HTDOCS"