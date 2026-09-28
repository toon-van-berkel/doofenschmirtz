<?php

class SubmissionController
{
    public function __construct()
    {
        $this->service = new SubmissionService(Database::connection());
    }

    private SubmissionService $service;

    public function view(): void
    {
        // TODO [SUBMISSION VIEW] (GET /submissions/view): Require an active user and integer submission_id query input.
        // Call SubmissionService::getSubmission(). Authorize the submitter, task creator, or explicitly eligible reviewer.
        // Return status, evidence_description, timestamps, submission_images metadata, and permitted history.
        // Use 400/401/403/404 as appropriate; do not expose unrelated private submissions.
        $this->notImplemented();
    }

    public function mine(): void
    {
        // TODO [MY SUBMISSIONS] (GET /submissions/mine): Require an active user and call getSubmissionsByUser() with session user_id.
        // Return task summaries, status, timestamps, evidence metadata, and appeal/verification state for that user only.
        // Use 401/403 for authentication/account-state failures and 200 with an empty list when applicable.
        $this->notImplemented();
    }

    public function create(): void
    {
        // TODO [SUBMISSION CREATION] (POST /submissions/create): Require an active user and accept task_id,
        // evidence_description, and eventually multipart/FormData evidence images.
        // Reject self-submission, require the task to be open, enforce UNIQUE(task_id,user_id), and require evidence images
        // in the final implementation. Insert status=submitted; submitting awards 0 points and does not complete the task.
        // Return 201 with the submission or 400/401/403/409/404 for invalid, unauthorized, duplicate, or missing data.
        $this->notImplemented();
    }

    private function notImplemented(): void
    {
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not implemented']);
    }
}
