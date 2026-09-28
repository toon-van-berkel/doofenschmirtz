<?php

class AdminService
{
    public function __construct(private PDO $db) {}

    public function listTasks(): array
    {
        // TODO [ADMIN]: Return tasks for role=admin moderation with validated status filters, pagination, creator summaries,
        // task_images, and stable ordering. This read must not alter state or award points.
        return [];
    }

    public function getTask(int $taskId): ?array
    {
        // TODO [ADMIN]: Load one task with creator, task_images, status, points, and relevant submissions/evidence.
        // Return null for missing task and do not allow non-admin callers through this service boundary.
        return null;
    }

    public function approveTask(int $adminId, array $input): ?array
    {
        // TODO [ADMIN]: Validate admin identity, task_id, and status=pending.
        // Set tasks.points to the approved reward and status=open, then create one task_created +20 transaction for tasks.created_by.
        // State update and reward must be atomic; retries must not duplicate the ledger entry.
        return null;
    }

    public function rejectTask(int $adminId, array $input): ?array
    {
        // TODO [ADMIN]: Validate admin identity, task_id, rejection reason, and status=pending.
        // Set status=rejected and preserve the reason using the available schema design. Do not set a reward or create points.
        // Make moderation atomic and reject repeated review of a non-pending task.
        return null;
    }
}
