<?php

class TaskController
{
    public function __construct()
    {
        $this->service = new TaskService(Database::connection());
    }

    private TaskService $service;

    public function list(): void //liam
    {
        // TODO [TASK LIST] (GET /tasks): Require an active user; accept optional status/filter and pagination query parameters.
        // Call TaskService::listOpenTasks(). Return approved/open task summaries and availability.
        // Use 401 for no session, 403 for inactive users, and 200 with an empty list when no tasks match.
        // This read must not award points or expose private creator fields.
        if ($this->activeUserId() === null) {
            return;
        }

        $tasks = $this->service->listOpenTasks();
        $this->respond(200, true, null, ['tasks' => $tasks]);
    }

    public function view(): void
    {
        // TODO [TASK VIEW] (GET /tasks/view): Require an active user and read integer task_id from the query string.
        // Call TaskService::getTask(). Return the task and public creator summary.
        // Validate visibility; use 400 for invalid input, 401/403 for access failure, and 404 for unknown/inaccessible tasks.
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
        // TODO [MY TASKS] (GET /tasks/mine): Require an active user and derive created_by from the session.
        // Call TaskService::getTasksByUser(). Return that user's tasks with status and moderation information.
        // Ignore any client-supplied user ID; use 401 for no session and 403 for inactive accounts.
        $userId = $this->activeUserId();

        if ($userId === null) {
            return;
        }

        $tasks = $this->service->getTasksByUser($userId);
        $this->respond(200, true, null, ['tasks' => $tasks]);
    }

    public function create(): void
    {
        // TODO [TASK CREATION] (POST /tasks/create): Require an active user and pass JSON title, description,
        // completion_criteria and optional location_description/latitude/longitude to the service.
        // Validate required fields and coordinate ranges. The user does not choose the reward: points stays NULL until approval.
        // New tasks always start pending. Return 201 with the created task, or 400/401/403 on validation/auth failure.
        // Do not award task_created (+20) here; that occurs once after admin approval.
        $userId = $this->activeUserId();

        if ($userId === null) {
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $body = is_array($body) ? $body : [];
        $task = $this->service->createTask($userId, $body);

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
