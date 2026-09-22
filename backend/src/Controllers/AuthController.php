<?php

class AuthController
{
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

    /*
        Registers a new user from the supplied JSON request body.

        This method validates the incoming username, email and password before
        passing the actual user creation to AuthService.
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

    /*
        Authenticates a user from the supplied JSON request body.

        The controller validates the request and delegates credential checking
        and session creation to AuthService.
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

    /*
        Reads the current request body and converts JSON input into an array.

        Invalid or empty JSON is returned as an empty array so the endpoint
        validation can handle missing request data consistently.
    */
    private function getJsonBody(): array
    {
        $body = json_decode(
            file_get_contents('php://input'),
            true
        );

        return is_array($body) ? $body : [];
    }

    /*
        Sends a JSON response using the common API response format.

        Additional response data can be supplied through $data, for example
        the user object returned after a successful login.
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