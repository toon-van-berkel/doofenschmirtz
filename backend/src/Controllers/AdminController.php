<?php

class AdminController
{
    private AdminService $service;

    public function __construct()
    {
        $this->service = new AdminService(Database::connection());
    }

    public function index(): void
    {
        // Only an active admin may access moderation data.
        $adminId = $this->adminId();
        if ($adminId === null) {
            return;
        }
        $this->respond(200, true, null, ['tasks' => $this->service->listTasks()]);
    }

    public function tasks(): void
    {
        // Return pending tasks for admin moderation without changing state or awarding points.
        // TODO [OPTIONAL]: Add status filters and pagination for larger queues.
        $adminId = $this->adminId();
        if ($adminId === null) {
            return;
        }
        $this->respond(200, true, null, ['tasks' => $this->service->listTasks()]);
    }

    public function taskView(): void
    {
        // Return the selected task and its moderation-safe details to an active admin.
        $adminId = $this->adminId();
        if ($adminId === null) {
            return;
        }
        $taskId = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);
        $task = $taskId ? $this->service->getTask($taskId) : null;
        if (!$task) { $this->respond(404, false, 'Task not found'); return; }
        $this->respond(200, true, null, ['task' => $task]);
    }

    public function approveTask(): void
    {
        // Admin approval opens the task, assigns its reward and records the creator reward atomically.
        $adminId = $this->adminId();
        if ($adminId === null) {
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $task = $this->service->approveTask($adminId, is_array($body) ? $body : []);
        if (!$task) { $this->respond(409, false, 'Task is not pending or input is invalid'); return; }
        $this->respond(200, true, 'Task approved', ['task' => $task]);
    }

    public function rejectTask(): void
    {
        // Admin rejection closes moderation without opening the task or awarding points.
        // TODO [OPTIONAL]: Persist a rejection reason if the schema later supports it.
        $adminId = $this->adminId();
        if ($adminId === null) {
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $task = $this->service->rejectTask($adminId, is_array($body) ? $body : []);
        if (!$task) { $this->respond(409, false, 'Task is not pending or input is invalid'); return; }
        $this->respond(200, true, 'Task rejected', ['task' => $task]);
    }

    private function adminId(): ?int
    {
        $id = Auth::userId();
        if ($id === null) { $this->respond(401, false, 'Not authenticated'); return null; }
        $query = Database::connection()->prepare('SELECT status, role FROM users WHERE id = ?');
        $query->execute([$id]);
        $user = $query->fetch();
        if (!$user || $user['status'] !== 'active') { $this->respond(403, false, 'Account is not active'); return null; }
        if ($user['role'] !== 'admin') { $this->respond(403, false, 'Admin access required'); return null; }
        return $id;
    }

    private function respond(int $status, bool $success, ?string $message, array $data = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        $response = ['success' => $success];
        if ($message !== null) $response['message'] = $message;
        echo json_encode([...$response, ...$data]);
    }
}
