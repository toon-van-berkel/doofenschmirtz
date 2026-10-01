<?php

class SubmissionService
{
    public function __construct(private PDO $db) {}

    public function createSubmission(int $userId, array $input): ?array
    {
        // Load and validate the request before creating any database or file state.
        $taskId = filter_var($input['task_id'] ?? null, FILTER_VALIDATE_INT);
        $evidence = trim($input['evidence_description'] ?? '');
        if ($userId < 1 || !$taskId || $evidence === '') {
            return null;
        }

        // Evidence uploads must use the expected PHP multipart structure.
        $uploads = $input['_files']['evidence_images'] ?? null;
        if ($uploads !== null && (
            !is_array($uploads) ||
            !isset($uploads['tmp_name'], $uploads['error'], $uploads['size'], $uploads['name']) ||
            !is_array($uploads['tmp_name']) ||
            !is_array($uploads['error']) ||
            !is_array($uploads['size']) ||
            !is_array($uploads['name'])
        )) {
            throw new RuntimeException('Invalid evidence image upload', 400);
        }

        // Closed tasks and task creators cannot submit their own work.
        $taskQuery = $this->db->prepare(
            'SELECT id, created_by, status FROM tasks WHERE id = ?'
        );
        $taskQuery->execute([$taskId]);
        $task = $taskQuery->fetch();
        if (!$task || $task['status'] !== 'open' || (int) $task['created_by'] === $userId) {
            return null;
        }

        // Evidence and its database metadata belong to one submission. If an
        // image fails, neither the submission nor partial files should remain.
        $storedFiles = [];
        $this->db->beginTransaction();

        try {
            $submission = $this->db->prepare(
                "INSERT INTO submissions (task_id, user_id, status, evidence_description)
                 VALUES (?, ?, 'submitted', ?)"
            );
            $submission->execute([$taskId, $userId, $evidence]);
            $submissionId = (int) $this->db->lastInsertId();

            // Validate and store every evidence image before committing the submission.
            foreach (($uploads['tmp_name'] ?? []) as $index => $temporaryPath) {
                $error = $uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                $size = (int) ($uploads['size'][$index] ?? 0);
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($error !== UPLOAD_ERR_OK || $size > 5242880 || !is_uploaded_file($temporaryPath)) {
                    throw new RuntimeException('Invalid evidence image upload', 400);
                }

                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime])) {
                    throw new RuntimeException('Unsupported evidence image type', 400);
                }

                $directory = dirname(__DIR__, 2) . '/public/uploads/submissions';
                if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
                    throw new RuntimeException('Evidence image storage failed', 500);
                }

                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                $targetPath = $directory . '/' . $filename;
                if (!move_uploaded_file($temporaryPath, $targetPath)) {
                    throw new RuntimeException('Evidence image storage failed', 500);
                }

                // Track written files so a later database or upload failure can
                // remove them again instead of leaving orphan evidence.
                $storedFiles[] = $targetPath;
                $image = $this->db->prepare(
                    'INSERT INTO submission_images
                     (submission_id, uploaded_by, image_url, original_filename)
                     VALUES (?, ?, ?, ?)'
                );
                $image->execute([
                    $submissionId,
                    $userId,
                    'uploads/submissions/' . $filename,
                    $uploads['name'][$index] ?? null,
                ]);
            }

            $this->db->commit();
            return $this->getSubmission($submissionId, $userId);
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            foreach ($storedFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            if ((int) $exception->errorInfo[1] === 1062) {
                return null;
            }
            throw $exception;
        } catch (Throwable $exception) {
            // Prevent half-created submissions and orphan files after any upload failure.
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

    private function images(int $submissionId): array
    {
        $query = $this->db->prepare(
            'SELECT id, image_url, original_filename, description, image_type, created_at
             FROM submission_images WHERE submission_id = ? ORDER BY id'
        );
        $query->execute([$submissionId]);
        return $query->fetchAll();
    }

    public function getSubmission(int $submissionId, int $userId): ?array
    {
        // A submission is visible only to its submitter or the task creator.
        $query = $this->db->prepare(
            'SELECT submissions.id, submissions.task_id, submissions.user_id,
                    submissions.status, submissions.evidence_description,
                    submissions.submitted_at, submissions.completed_at,
                    tasks.title, tasks.created_by
             FROM submissions JOIN tasks ON tasks.id = submissions.task_id
             WHERE submissions.id = ?
               AND (submissions.user_id = ? OR tasks.created_by = ?)'
        );
        $query->execute([$submissionId, $userId, $userId]);
        $submission = $query->fetch();
        if (!$submission) {
            return null;
        }
        $submission['images'] = $this->images($submissionId);
        return $submission;
    }

    public function getSubmissionsByUser(int $userId): array
    {
        $query = $this->db->prepare(
            'SELECT submissions.id, submissions.task_id, submissions.status,
                    submissions.evidence_description, submissions.submitted_at,
                    submissions.completed_at, tasks.title
             FROM submissions JOIN tasks ON tasks.id = submissions.task_id
             WHERE submissions.user_id = ?
             ORDER BY submissions.submitted_at DESC, submissions.id DESC'
        );
        $query->execute([$userId]);
        $rows = $query->fetchAll();
        foreach ($rows as &$row) {
            $row['images'] = $this->images((int) $row['id']);
        }
        return $rows;
    }
}
