<?php

class TaskController
{
    public function __construct()
    {
        $this->service = new TaskService(Database::connection());
    }

    private TaskService $service;

    public function list(): void
    {
        // TODO [TASK LIST] (GET /tasks): Require an active user; accept optional status/filter and pagination query parameters.
        // Call TaskService::listOpenTasks(). Return approved/open task summaries, task_images metadata, and availability.
        // Use 401 for no session, 403 for inactive users, and 200 with an empty list when no tasks match.
        // This read must not award points or expose private creator fields.
        $this->notImplemented();
    }

    public function view(): void
    {
        // TODO [TASK VIEW] (GET /tasks/view): Require an active user and read integer task_id from the query string.
        // Call TaskService::getTask(). Return the task, public creator summary, and task_images metadata.
        // Validate visibility; use 400 for invalid input, 401/403 for access failure, and 404 for unknown/inaccessible tasks.
        $this->notImplemented();
    }

    public function mine(): void
    {
        // TODO [MY TASKS] (GET /tasks/mine): Require an active user and derive created_by from the session.
        // Call TaskService::getTasksByUser(). Return that user's tasks with status and moderation information.
        // Ignore any client-supplied user ID; use 401 for no session and 403 for inactive accounts.
        $this->notImplemented();
    }

    public function create(): void
    {
        // TODO [TASK CREATION] (POST /tasks/create): Require an active user and pass JSON title, description,
        // completion_criteria, optional location_description/latitude/longitude, and future task-image metadata to the service.
        // Validate required fields and coordinate ranges. The user does not choose the reward: points stays NULL until approval.
        // New tasks always start pending. Return 201 with the created task, or 400/401/403 on validation/auth failure.
        // Do not award task_created (+20) here; that occurs once after admin approval.
        $this->notImplemented();
    }

    private function notImplemented(): void
    {
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not implemented']);
    }
}
