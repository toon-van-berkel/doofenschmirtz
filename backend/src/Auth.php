<?php

/** Small authorization foundation for future controllers. */
class Auth
{
    public static function userId(): ?int
    {
        // TODO [AUTHORIZATION]: Return the integer user ID stored by AuthService::login in $_SESSION['user_id'].
        // Return null when no authenticated session exists. Never accept a user ID from request input for identity.
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function requireUser(): int
    {
        // TODO [AUTHORIZATION]: Require an authenticated session and return its user ID.
        // Missing session: the eventual HTTP layer must respond 401 (not authenticated), without querying protected data.
        // This helper must not decide role or account status; those checks belong to the more specific helpers below.
        $id = self::userId();
        if ($id === null) throw new RuntimeException('Authentication required', 401);
        return $id;
    }

    public static function currentUser(): ?array
    {
        // TODO [AUTHORIZATION]: Resolve the session ID through the existing users table/AuthService and return public fields only.
        // Expected result: id, username, email, role, and status as appropriate; never password_hash.
        // Missing or deleted user should return null and callers should treat the session as unauthenticated (401).
        return null;
    }

    public static function requireActiveUser(): int
    {
        // TODO [AUTHORIZATION]: Require a logged-in user, load users.status, and allow only status=active.
        // Missing session is 401; authenticated but inactive/suspended is 403. Do not mutate account state here.
        return self::requireUser();
    }

    public static function requireAdmin(): int
    {
        // TODO [AUTHORIZATION]: Require an active user and verify users.role=admin server-side.
        // Missing session is 401; authenticated non-admin is 403. Frontend hiding is not an authorization control.
        return self::requireActiveUser();
    }
}
