<?php

class TaskService
{

    public function __construct(private PDO $db) {}

    public function listOpenTasks(): array //liam
    {
        // TODO [TASK LIST]: Read tasks with status=open and safe public fields.
        // Accept validated filters/pagination and do not return private creator data or award points.
        $statement = $this->db->prepare(
            'SELECT id, title, description, completion_criteria,
                    location_description, latitude, longitude, points, status, created_at
             FROM tasks
             WHERE status = ?
             ORDER BY created_at DESC'
        );
        $statement->execute(['open']);

        return $statement->fetchAll();
    }

    public function getTask(int $taskId): ?array
    {
        // TODO [TASK VIEW]: Load tasks.id with creator summary, status, points, and submission availability.
        // Return null for missing/inaccessible records; this read must not change state.
        if ($taskId < 1) {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT tasks.id, tasks.title, tasks.description, tasks.completion_criteria,
                    tasks.location_description, tasks.latitude, tasks.longitude,
                    tasks.points, tasks.status, tasks.created_at,
                    users.id AS creator_id, users.username AS creator_username
             FROM tasks
             JOIN users ON users.id = tasks.created_by
             WHERE tasks.id = ? AND tasks.status = ?
             LIMIT 1'
        );
        $statement->execute([$taskId, 'open']);
        $task = $statement->fetch();

        if (!$task) {
            return null;
        }

        $task['submission_available'] = $task['status'] === 'open';

        return $task;
    }

    public function getTasksByUser(int $userId): array //liam
    {
        // TODO [MY TASKS]: Filter tasks.created_by=$userId and return caller-owned tasks with moderation states.
        // Include relevant submission/review summaries without accepting a substitute user ID.
        if ($userId < 1) {
            return [];
        }

        $statement = $this->db->prepare(
            'SELECT tasks.id, tasks.title, tasks.description, tasks.completion_criteria,
                    tasks.location_description, tasks.latitude, tasks.longitude,
                    tasks.points, tasks.status, tasks.created_at,
                    COUNT(submissions.id) AS submission_count
             FROM tasks
             LEFT JOIN submissions ON submissions.task_id = tasks.id
             WHERE tasks.created_by = ?
             GROUP BY tasks.id
             ORDER BY tasks.created_at DESC'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function createTask(int $userId, array $input): ?array
    {
        // TODO [TASK CREATION]: Validate title, description, completion_criteria, optional location_description,
        // latitude (-90..90), and longitude (-180..180).
        // Insert tasks.created_by=$userId with status=pending and points=NULL; the user never chooses the reward.
        // Do not write point_transactions here.
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $criteria = trim($input['completion_criteria'] ?? '');
        $location = trim($input['location_description'] ?? '');
        $latitude = $input['latitude'] ?? null;
        $longitude = $input['longitude'] ?? null;

        if ($userId < 1 || $title === '' || $description === '' || $criteria === '') {
            return null;
        }

        if (strlen($title) > 255 || strlen($location) > 500) {
            return null;
        }

        if ($latitude !== null && (!is_numeric($latitude) || $latitude < -90 || $latitude > 90)) {
            return null;
        }

        if ($longitude !== null && (!is_numeric($longitude) || $longitude < -180 || $longitude > 180)) {
            return null;
        }

        $statement = $this->db->prepare(
            'INSERT INTO tasks
                (created_by, title, description, completion_criteria,
                 location_description, latitude, longitude, points, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?)'
        );
        $statement->execute([
            $userId,
            $title,
            $description,
            $criteria,
            $location === '' ? null : $location,
            $latitude === null ? null : (float) $latitude,
            $longitude === null ? null : (float) $longitude,
            'pending'
        ]);

        $taskId = (int) $this->db->lastInsertId();

        $statement = $this->db->prepare(
            'SELECT id, created_by, title, description, completion_criteria,
                    location_description, latitude, longitude, points, status, created_at
             FROM tasks
             WHERE id = ?'
        );
        $statement->execute([$taskId]);
        $task = $statement->fetch();

        if (!$task) {
            return null;
        }

        return $task;
    }
}
