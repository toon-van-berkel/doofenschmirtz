$ErrorActionPreference = "Stop"

# Builds a disposable hosting package for InfinityFree.
# Source files should always be edited in frontend/ and backend/, never in deploy/.

$root = Split-Path -Parent $PSScriptRoot

$deploy = Join-Path $root "deploy"
$htdocs = Join-Path $deploy "htdocs"
$api = Join-Path $htdocs "api"
$privateBackend = Join-Path $htdocs "_backend"

Write-Host "Creating deployment package..."

if (Test-Path $deploy) {
    Remove-Item $deploy -Recurse -Force
}

New-Item $htdocs -ItemType Directory -Force | Out-Null
New-Item $api -ItemType Directory -Force | Out-Null
New-Item $privateBackend -ItemType Directory -Force | Out-Null


# Frontend
Copy-Item `
    (Join-Path $root "frontend\*") `
    $htdocs `
    -Recurse `
    -Force


# Backend
Copy-Item `
    (Join-Path $root "backend\*") `
    $privateBackend `
    -Recurse `
    -Force


# Never deploy the developer-specific database configuration.
Remove-Item `
    (Join-Path $privateBackend "config\database.php") `
    -Force `
    -ErrorAction SilentlyContinue


$utf8NoBom = New-Object System.Text.UTF8Encoding -ArgumentList $false


# Prevent direct HTTP access to backend source and configuration files.
$backendHtaccess = @'
Require all denied
'@

[System.IO.File]::WriteAllText(
    (Join-Path $privateBackend ".htaccess"),
    $backendHtaccess,
    $utf8NoBom
)


# Public API entry point.
# Production requests use /api while the backend router expects routes such as
# /auth/login, so the deployment adapter removes the /api prefix.
$apiIndex = @'
<?php

$_SERVER['REQUEST_URI'] =
    preg_replace('#^/api#', '', $_SERVER['REQUEST_URI']) ?: '/';

require_once __DIR__ . '/../_backend/public/index.php';
'@

[System.IO.File]::WriteAllText(
    (Join-Path $api "index.php"),
    $apiIndex,
    $utf8NoBom
)


# Route all API requests through the deployment entry point.
$apiHtaccess = @'
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^ index.php [L,QSA]
'@

[System.IO.File]::WriteAllText(
    (Join-Path $api ".htaccess"),
    $apiHtaccess,
    $utf8NoBom
)


Get-ChildItem $deploy -Recurse -Force -File |
    Where-Object {
        $_.Name -in @(
            ".env",
            ".env.local",
            ".DS_Store"
        )
    } |
    Remove-Item -Force


Write-Host ""
Write-Host "Deployment package ready:"
Write-Host $htdocs