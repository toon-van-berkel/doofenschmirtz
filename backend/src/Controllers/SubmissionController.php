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
        // Return evidence only to the submitter or task creator authorized to inspect it.
        $userId = $this->activeUserId();
        if ($userId === null) {
            return;
        }
        $id = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);
        if (!$id) { $this->respond(400, false, 'Invalid submission ID'); return; }
        $submission = $this->service->getSubmission($id, $userId);
        if (!$submission) { $this->respond(404, false, 'Submission not found'); return; }
        $this->respond(200, true, null, ['submission' => $submission]);
    }

    public function mine(): void
    {
        // My submissions always use the authenticated session user and never accept
        // a client-controlled user ID.
        $userId = $this->activeUserId();
        if ($userId === null) {
            return;
        }
        $this->respond(200, true, null, ['submissions' => $this->service->getSubmissionsByUser($userId)]);
    }

    public function create(): void
    {
        // Evidence can be submitted only for another user's open task. Creation
        // stores the submission and optional images atomically and awards no points.
        $userId = $this->activeUserId();
        if ($userId === null) {
            return;
        }
        $body = $_POST ?: json_decode(file_get_contents('php://input'), true);
        $body = is_array($body) ? $body : [];
        $body['_files'] = $_FILES;
        try { $submission = $this->service->createSubmission($userId, $body); }
        catch (RuntimeException $exception) { $this->respond($exception->getCode() === 400 ? 400 : 500, false, $exception->getMessage()); return; }
        if (!$submission) { $this->respond(400, false, 'Invalid, unavailable, or duplicate submission'); return; }
        $this->respond(201, true, 'Submission created', ['submission' => $submission]);
    }

    private function activeUserId(): ?int
    {
        $id = Auth::userId(); if ($id === null) { $this->respond(401, false, 'Not authenticated'); return null; }
        $s = Database::connection()->prepare('SELECT status FROM users WHERE id = ?'); $s->execute([$id]); $u = $s->fetch();
        if (!$u) { $this->respond(401, false, 'Not authenticated'); return null; }
        if ($u['status'] !== 'active') { $this->respond(403, false, 'Account is not active'); return null; }
        return $id;
    }

    private function respond(int $status, bool $success, ?string $message, array $data = []): void
    {
        http_response_code($status); header('Content-Type: application/json');
        $response = ['success' => $success]; if ($message !== null) $response['message'] = $message;
        echo json_encode([...$response, ...$data]);
    }

    private function notImplemented(): void
    {
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not implemented']);
    }
}
