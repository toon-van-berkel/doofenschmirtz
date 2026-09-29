<?php

class TaskService
{
    public function __construct(private PDO $db) {}

    public function listOpenTasks(): array
    {
        // TODO [TASK LIST]: Read tasks with status=open, safe public fields, and task_images metadata.
        // Accept validated filters/pagination and do not return private creator data or award points.
        return [];
    }

    public function getTask(int $taskId): ?array
    {
        // TODO [TASK VIEW]: Load tasks.id with task_images, creator summary, status, points, and submission availability.
        // Return null for missing/inaccessible records; this read must not change state.
        return null;
    }

    public function getTasksByUser(int $userId): array
    {
        // TODO [MY TASKS]: Filter tasks.created_by=$userId and return caller-owned tasks with moderation states.
        // Include relevant submission/review summaries without accepting a substitute user ID.
        return [];
    }

    public function createTask(int $userId, array $input): ?array
    {
        // TODO [TASK CREATION]: Validate title, description, completion_criteria, optional location_description,
        // latitude (-90..90), longitude (-180..180), and future task image metadata.
        // Insert tasks.created_by=$userId with status=pending and points=NULL; the user never chooses the reward.
        // Use a transaction if task_images are added. Do not write point_transactions here.
        return null;
    }
}
