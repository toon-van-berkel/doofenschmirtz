<?php

class TaskService
{
    // --- Team implementation: Tasks ---
    // Backend scaffold/specification: Toon van Berkel
    // Feature implementation: Liam Plokkaar
    // Source branch: taskpage
    // Integration by Toon van Berkel: adapted implementation to current schema.
    public function __construct(private PDO $db) {}

    public function listOpenTasks(): array
    {
        // Only open tasks belong in the public list because these are the tasks
        // another user is still allowed to complete.
        $query = $this->db->prepare(
            "SELECT id, title, description, completion_criteria, location_description,
                    latitude, longitude, points, status, created_at
             FROM tasks WHERE status = 'open' ORDER BY created_at DESC, id DESC"
        );
        $query->execute();
        $rows = $query->fetchAll();
        foreach ($rows as &$row) {
            // Images are stored separately, so attach their metadata before
            // returning each task to the frontend.
            $images = $this->db->prepare(
                'SELECT id, image_url, description, created_at FROM task_images
                 WHERE task_id = ? ORDER BY id'
            );
            $images->execute([$row['id']]);
            $row['images'] = $images->fetchAll();
        }
        return $rows;
    }

    public function getTask(int $taskId): ?array
    {
        if ($taskId < 1) {
            return null;
        }
        // Task detail is limited to open tasks because this endpoint supports
        // users who want to complete an available task.
        $query = $this->db->prepare(
            "SELECT tasks.id, tasks.title, tasks.description, tasks.completion_criteria,
                    tasks.location_description, tasks.latitude, tasks.longitude, tasks.points,
                    tasks.status, tasks.created_at, users.id AS creator_id,
                    users.username AS creator_username
             FROM tasks JOIN users ON users.id = tasks.created_by
             WHERE tasks.id = ? AND tasks.status = 'open' LIMIT 1"
        );
        $query->execute([$taskId]);
        $task = $query->fetch();
        if (!$task) {
            return null;
        }
        // Include the creator so the frontend can show task ownership while
        // keeping creator and completer responsibilities separate.
        $images = $this->db->prepare(
            'SELECT id, image_url, description, created_at FROM task_images
             WHERE task_id = ? ORDER BY id'
        );
        $images->execute([$taskId]);
        $task['images'] = $images->fetchAll();
        // An open task may receive completion evidence from an eligible user.
        $task['submission_available'] = true;
        return $task;
    }

    public function getTasksByUser(int $userId): array
    {
        // Creators need to see all of their own tasks, including tasks that are
        // pending, rejected or completed and therefore no longer open.
        $query = $this->db->prepare(
            'SELECT tasks.id, tasks.title, tasks.description, tasks.completion_criteria,
                    tasks.location_description, tasks.latitude, tasks.longitude, tasks.points,
                    tasks.status, tasks.created_at,
                    (SELECT COUNT(*) FROM submissions WHERE submissions.task_id = tasks.id)
                    AS submission_count
             FROM tasks WHERE tasks.created_by = ?
             ORDER BY tasks.created_at DESC, tasks.id DESC'
        );
        $query->execute([$userId]);
        return $query->fetchAll();
    }

    public function createTask(int $userId, array $input, array $files = []): ?array
    {
        // Validate task fields before creating database or filesystem state.
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $criteria = trim($input['completion_criteria'] ?? '');
        $location = trim($input['location_description'] ?? '');
        $latitude = $input['latitude'] ?? null;
        $longitude = $input['longitude'] ?? null;
        if (
            $userId < 1 ||
            $title === '' ||
            $description === '' ||
            $criteria === '' ||
            strlen($title) > 255 ||
            strlen($location) > 500
        ) {
            return null;
        }

        // Coordinates come from browser geolocation. Validate their geographic
        // range before storing client-provided values.
        if (
            $latitude !== null &&
            $latitude !== '' &&
            (!is_numeric($latitude) || $latitude < -90 || $latitude > 90)
        ) {
            return null;
        }
        if (
            $longitude !== null &&
            $longitude !== '' &&
            (!is_numeric($longitude) || $longitude < -180 || $longitude > 180)
        ) {
            return null;
        }

        $uploads = $files['task_images'] ?? null;
        // Reject malformed multipart data before the transaction starts so an
        // invalid upload cannot create partial application state.
        if ($uploads !== null && (
            !is_array($uploads) || !isset($uploads['tmp_name'], $uploads['error'], $uploads['size'], $uploads['name'])
            || !is_array($uploads['tmp_name']) || !is_array($uploads['error'])
            || !is_array($uploads['size']) || !is_array($uploads['name'])
        )) {
            throw new RuntimeException('Invalid task image upload', 400);
        }

        // The task, image metadata and uploaded files belong together. If one
        // part fails, none of the task creation should remain.
        $storedFiles = [];
        $this->db->beginTransaction();
        try {
            // New tasks start as pending. An admin assigns the reward and
            // approves the task before it becomes visible as open.
            $insert = $this->db->prepare(
                'INSERT INTO tasks
                 (created_by, title, description, completion_criteria, location_description,
                  latitude, longitude, points, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?)'
            );
            $insert->execute([
                $userId, $title, $description, $criteria, $location === '' ? null : $location,
                $latitude === '' ? null : $latitude, $longitude === '' ? null : $longitude, 'pending',
            ]);
            $taskId = (int) $this->db->lastInsertId();

            // Validate and save every task image before committing the task.
            foreach (($uploads['tmp_name'] ?? []) as $index => $temporaryPath) {
                $error = $uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                $size = (int) ($uploads['size'][$index] ?? 0);
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($error !== UPLOAD_ERR_OK || $size > 5242880 || !is_uploaded_file($temporaryPath)) {
                    throw new RuntimeException('Invalid task image upload', 400);
                }
                // Trust the detected MIME type instead of the browser filename.
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime])) {
                    throw new RuntimeException('Unsupported task image type', 400);
                }
                $directory = dirname(__DIR__, 2) . '/public/uploads/tasks';
                if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
                    throw new RuntimeException('Task image storage failed', 500);
                }
                // Generated names prevent user input from controlling server
                // paths or overwriting another stored file.
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                $targetPath = $directory . '/' . $filename;
                if (!move_uploaded_file($temporaryPath, $targetPath)) {
                    throw new RuntimeException('Task image storage failed', 500);
                }
                $storedFiles[] = $targetPath;
                // Store a public relative path, never the physical server path.
                $image = $this->db->prepare(
                    'INSERT INTO task_images (task_id, image_url, description)
                     VALUES (?, ?, ?)'
                );
                $image->execute([
                    $taskId,
                    'uploads/tasks/' . $filename,
                    $uploads['name'][$index] ?? null,
                ]);
            }

            // The task and every requested image succeeded, so creation can
            // safely become permanent.
            $this->db->commit();
            $query = $this->db->prepare(
                'SELECT id, created_by, title, description, completion_criteria, location_description,
                        latitude, longitude, points, status, created_at FROM tasks WHERE id = ?'
            );
            $query->execute([$taskId]);
            return $query->fetch() ?: null;
        } catch (Throwable $exception) {
            // Roll back the database and remove already-written files to prevent
            // half-created tasks and orphaned uploads.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            foreach ($storedFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            throw $exception;
        }
    }
}
