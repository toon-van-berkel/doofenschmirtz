<?php

class AdminController
{
    public function __construct()
    {
        $this->service = new AdminService(Database::connection());
    }

    private AdminService $service;

    public function index(): void
    {
        // TODO [ADMIN] (GET /admin): Require active role=admin and return aggregate moderation counts/queue summaries only.
        $this->notImplemented();
    }

    public function tasks(): void
    {
        // TODO [ADMIN] (GET /admin/tasks): Require role=admin; accept status/filter/pagination query parameters and call listTasks().
        // Return moderation-safe task summaries, including pending/rejected/open states as appropriate.
        $this->notImplemented();
    }

    public function taskView(): void
    {
        // TODO [ADMIN] (GET /admin/tasks/view): Require role=admin and integer task_id query input.
        // Call AdminService::getTask() and return creator, task_images, submissions, and moderation state.
        $this->notImplemented();
    }

    public function approveTask(): void
    {
        // TODO [ADMIN] (POST /admin/tasks/approve): Require role=admin and accept task_id in the JSON body.
        // Approve only pending tasks: set task.points to the approved admin-defined reward and status=open.
        // Award creator task_created +20 exactly once in the same transaction; return 409 for non-pending/already-reviewed tasks.
        $this->notImplemented();
    }

    public function rejectTask(): void
    {
        // TODO [ADMIN] (POST /admin/tasks/reject): Require role=admin and accept task_id plus rejection reason.
        // Reject only pending tasks, set status=rejected, preserve the reason using the available design, and award no +20.
        // Return 409 for already-reviewed tasks and commit the moderation state atomically.
        $this->notImplemented();
    }

    private function notImplemented(): void
    {
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not implemented']);
    }
}
