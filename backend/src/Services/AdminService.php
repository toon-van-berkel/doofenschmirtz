<?php

class AdminService
{
    public function __construct(private PDO $db) {}

    public function listTasks(): array
    {
        // Return pending tasks and their images for admin moderation.
        // TODO [OPTIONAL]: Add status filters and pagination for larger queues.
        $statement = $this->db->query(
            "SELECT tasks.id, tasks.title, tasks.description, tasks.completion_criteria, tasks.location_description, tasks.created_by, tasks.points, tasks.status, tasks.created_at,
                    users.username AS creator_username
             FROM tasks JOIN users ON users.id = tasks.created_by
             WHERE tasks.status = 'pending' ORDER BY tasks.created_at ASC, tasks.id ASC"
        );
        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            $images = $this->db->prepare(
                'SELECT id, image_url, description, created_at FROM task_images WHERE task_id = ?'
            );
            $images->execute([$row['id']]);
            $row['images'] = $images->fetchAll();
        }
        return $rows;
    }

    public function getTask(int $taskId): ?array
    {
        // Load the task context needed for moderation; the controller enforces admin access.
        $statement = $this->db->prepare(
            'SELECT tasks.*, users.username AS creator_username FROM tasks
             JOIN users ON users.id = tasks.created_by WHERE tasks.id = ?'
        );
        $statement->execute([$taskId]);
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }
        $images = $this->db->prepare(
            'SELECT id, image_url, description, created_at FROM task_images WHERE task_id = ?'
        );
        $images->execute([$taskId]);
        $row['images'] = $images->fetchAll();
        return $row;
    }

    public function approveTask(int $adminId, array $input): ?array
    {
        // Approval opens the task and records the creator reward in the same transaction.
        $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT);
        $points = filter_var($input['points'] ?? null, FILTER_VALIDATE_INT);
        if (!$taskId || $points === false || $points < 0) {
            return null;
        }
        $this->db->beginTransaction();
        try {
            $query = $this->db->prepare('SELECT id, created_by, status FROM tasks WHERE id = ? FOR UPDATE');
            $query->execute([$taskId]);
            $task = $query->fetch();
            if (!$task || $task['status'] !== 'pending') {
                $this->db->rollBack();
                return null;
            }
            $this->db->prepare("UPDATE tasks SET points = ?, status = 'open' WHERE id = ?")->execute([$points, $taskId]);
            $this->db->prepare("INSERT INTO point_transactions (user_id, task_id, amount, type) VALUES (?, ?, 20, 'task_created')")->execute([$task['created_by'], $taskId]);
            $this->db->commit();
            return $this->getTask($taskId);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function rejectTask(int $adminId, array $input): ?array
    {
        // Rejection closes moderation without opening the task or awarding points.
        // TODO [OPTIONAL]: Persist a rejection reason if the schema later supports it.
        $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$taskId) {
            return null;
        }
        $query = $this->db->prepare("UPDATE tasks SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $query->execute([$taskId]);
        return $query->rowCount() === 1 ? $this->getTask($taskId) : null;
    }
}
