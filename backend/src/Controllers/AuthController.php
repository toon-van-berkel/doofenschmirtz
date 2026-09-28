<?php

class AuthController
{
    /**
     * Handles HTTP input and JSON output for authentication endpoints.
     *
     * AuthService contains the database and password logic. This controller
     * stays focused on the boundary between an HTTP request and that service.
     */
    private AuthService $authService;

    /*
        Creates the authentication service used by this controller.

        The controller itself handles HTTP input and responses, while AuthService
        contains the actual authentication and database logic.
    */
    public function __construct()
    {
        $this->authService = new AuthService(
            Database::connection()
        );
    }

    /**
     * Registers a user from a JSON request body.
     *
     * Basic request checks and HTTP status selection happen here; persistence
     * and password hashing remain inside AuthService.
     */
    public function register(): void
    {
        $body = $this->getJsonBody();

        $username = trim($body['username'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$username || !$email || !$password) {
            $this->respond(
                400,
                false,
                'Username, email and password are required'
            );

            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(
                400,
                false,
                'Invalid email address'
            );

            return;
        }

        if ($this->authService->userExists($username, $email)) {
            $this->respond(
                409,
                false,
                'Username or email already exists'
            );

            return;
        }

        $this->authService->register(
            $username,
            $email,
            $password
        );

        $this->respond(
            201,
            true,
            'Account created'
        );
    }

    /**
     * Authenticates a user from a JSON request body.
     *
     * AuthService verifies the password and regenerates the session ID. Only
     * public user data is returned; the password hash never leaves the server.
     */
    public function login(): void
    {
        $body = $this->getJsonBody();

        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) {
            $this->respond(
                400,
                false,
                'Email and password are required'
            );

            return;
        }

        $user = $this->authService->login(
            $email,
            $password
        );

        if ($user === null) {
            $this->respond(
                401,
                false,
                'Invalid email or password'
            );

            return;
        }

        $this->respond(
            200,
            true,
            null,
            [
                'user' => $user
            ]
        );
    }

    public function me(): void
    {
        // Login stores only the user ID in the PHP session. Resolving that ID
        // here lets the frontend restore the user after a page refresh without
        // storing an authentication token in JavaScript.
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->respond(401, false, 'Not authenticated');

            return;
        }

        $user = $this->authService->findById((int) $userId);

        if (!$user) {
            unset($_SESSION['user_id']);
            $this->respond(401, false, 'Not authenticated');

            return;
        }

        $this->respond(200, true, null, [
            'user' => $user
        ]);
    }

    public function logout(): void
    {
        /**
         * Logout clears both sides of the session: server-side session data
         * and the browser cookie that points to that session.
         */
        $cookie = session_get_cookie_params();

        $_SESSION = [];

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'] ?? '/',
            'domain' => $cookie['domain'] ?? '',
            'secure' => $cookie['secure'] ?? false,
            'httponly' => $cookie['httponly'] ?? true,
            'samesite' => $cookie['samesite'] ?? 'Lax'
        ]);

        session_destroy();

        $this->respond(200, true, 'Logged out');
    }

    /**
     * Reads the JSON request body used by registration and login.
     *
     * Invalid or empty JSON becomes an empty array so endpoint validation can
     * return a normal client error instead of a PHP warning.
     */
    private function getJsonBody(): array
    {
        $body = json_decode(
            file_get_contents('php://input'),
            true
        );

        return is_array($body) ? $body : [];
    }

    /**
     * Sends the shared JSON response format used by all API endpoints.
     *
     * Additional data is limited to safe public values such as the user object;
     * sensitive database fields are never passed into this method.
     */
    private function respond(
        int $status,
        bool $success,
        ?string $message = null,
        array $data = []
    ): void {
        http_response_code($status);
        header('Content-Type: application/json');

        $response = [
            'success' => $success
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        echo json_encode([
            ...$response,
            ...$data
        ]);
    }
}
