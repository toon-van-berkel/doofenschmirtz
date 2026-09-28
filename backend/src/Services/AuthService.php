<?php

class AuthService
{
    public function __construct(
        private PDO $db
    ) {}

    /*
        Checks whether a username or email address is already registered.

        This is used during registration so the API can return a clear conflict
        response before attempting to insert a duplicate user.
    */
    public function userExists(
        string $username,
        string $email
    ): bool {
        $stmt = $this->db->prepare(
            'SELECT id
             FROM users
             WHERE email = ? OR username = ?
             LIMIT 1'
        );

        $stmt->execute([
            $email,
            $username
        ]);

        return $stmt->fetch() !== false;
    }

    /*
        Creates a new user in the database.

        The plain-text password is converted into a secure password hash before
        it is stored. Plain-text passwords should never be stored directly.
    */
    public function register(
        string $username,
        string $email,
        string $password
    ): void {
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $this->db->prepare(
            'INSERT INTO users (
                username,
                email,
                password_hash
            )
            VALUES (?, ?, ?)'
        );

        $stmt->execute([
            $username,
            $email,
            $passwordHash
        ]);
    }

    /*
        Attempts to authenticate a user using an email address and password.

        When the credentials are correct, the user ID is stored in the current
        PHP session and the public user data is returned.

        Returns null when the credentials are invalid.
    */
    public function login(
        string $email,
        string $password
    ): ?array {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, password_hash
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([
            $email
        ]);

        $user = $stmt->fetch();

        if (
            !$user ||
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {
            return null;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];

        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email
             FROM users
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ];
    }
}
