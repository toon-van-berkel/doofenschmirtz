<?php

/** Shared authorization foundation for controllers that use these helpers. */
class Auth
{
    public static function userId(): ?int
    {
        // Identity comes from the authenticated session; request parameters never
        // decide which user owns protected data.
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function requireUser(): int
    {
        // Require a session identity before protected code is allowed to continue.
        // Account status and role checks belong to the more specific helpers.
        $id = self::userId();
        if ($id === null) throw new RuntimeException('Authentication required', 401);
        return $id;
    }

    public static function currentUser(): ?array
    {
        // TODO [CORE]: Resolve the session ID through users and return public fields only.
        // Expected result: id, username, email, role, and status as appropriate; never password_hash.
        // Missing or deleted user should return null and callers should treat the session as unauthenticated (401).
        return null;
    }

    public static function requireActiveUser(): int
    {
        // TODO [FUTURE REFACTOR]: Centralize the active-user check currently
        // performed by individual controllers.
        // Missing session is 401; authenticated but inactive/suspended is 403. Do not mutate account state here.
        return self::requireUser();
    }

    public static function requireAdmin(): int
    {
        // TODO [FUTURE REFACTOR]: Centralize the active-admin check currently
        // performed by individual controllers.
        // Missing session is 401; authenticated non-admin is 403. Frontend hiding is not an authorization control.
        return self::requireActiveUser();
    }
}
