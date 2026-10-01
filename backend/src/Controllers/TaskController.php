<?php

class TaskController
{
    // --- Team implementation: Tasks ---
    // Backend scaffold/specification: Toon van Berkel
    // Feature implementation: Liam Plokkaar
    // Source branch: taskpage
    // Integration by Toon van Berkel: adapted implementation to current auth, router and schema.

    public function __construct()
    {
        $this->service = new TaskService(Database::connection());
    }

    private TaskService $service;

    public function list(): void //liam
    {
        // Only authenticated active users can browse tasks that are currently open.
        // TODO [OPTIONAL]: add filtering and pagination if the list grows.
        if ($this->activeUserId() === null) {
            return;
        }

        $tasks = $this->service->listOpenTasks();
        $this->respond(200, true, null, ['tasks' => $tasks]);
    }

    public function view(): void
    {
        // Return only an authenticated user's public view of an open task.
        if ($this->activeUserId() === null) {
            return;
        }

        $taskId = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);

        if (!$taskId || $taskId < 1) {
            $this->respond(400, false, 'Invalid task ID');
            return;
        }

        $task = $this->service->getTask($taskId);

        if ($task === null) {
            $this->respond(404, false, 'Task not found');
            return;
        }

        $this->respond(200, true, null, ['task' => $task]);
    }

    public function mine(): void // Liam
    {
        // Load the creator's own tasks from the session identity, never from client input.
        $userId = $this->activeUserId();

        if ($userId === null) {
            return;
        }

        $tasks = $this->service->getTasksByUser($userId);
        $this->respond(200, true, null, ['tasks' => $tasks]);
    }

    public function create(): void
    {
        // New tasks start pending; admin approval assigns the reward and makes them open.
        $userId = $this->activeUserId();

        if ($userId === null) {
            return;
        }

        $body = $_POST ?: json_decode(file_get_contents('php://input'), true);
        $body = is_array($body) ? $body : [];
        try { $task = $this->service->createTask($userId, $body, $_FILES); }
        catch (RuntimeException $exception) { $this->respond($exception->getCode() === 400 ? 400 : 500, false, $exception->getMessage()); return; }

        if ($task === null) {
            $this->respond(400, false, 'Invalid task data');
            return;
        }

        $this->respond(201, true, 'Task created', ['task' => $task]);
    }

    private function activeUserId(): ?int
    {
        $userId = Auth::userId();

        if ($userId === null) {
            $this->respond(401, false, 'Not authenticated');
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT status FROM users WHERE id = ? LIMIT 1'
        );
        $statement->execute([$userId]);
        $user = $statement->fetch();

        if (!$user) {
            unset($_SESSION['user_id']);
            $this->respond(401, false, 'Not authenticated');
            return null;
        }

        if ($user['status'] !== 'active') {
            $this->respond(403, false, 'Account is not active');
            return null;
        }

        return $userId;
    }

    private function respond(
        int $status,
        bool $success,
        ?string $message = null,
        array $data = []
    ): void {
        http_response_code($status);
        header('Content-Type: application/json');

        $response = ['success' => $success];

        if ($message !== null) {
            $response['message'] = $message;
        }

        echo json_encode([...$response, ...$data]);
    }
}
