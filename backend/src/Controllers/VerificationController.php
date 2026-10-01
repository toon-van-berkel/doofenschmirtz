<?php

class VerificationController
{
    public function __construct()
    {
        $this->service = new VerificationService(Database::connection());
    }

    private VerificationService $service;

    public function creator(): void
    {
        // Return only submitted work waiting for a decision by this user's tasks.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $this->respond(200, true, null, ['submissions' => $this->service->getCreatorQueue($id)]);
    }

    public function creatorReview(): void
    {
        // Only the task creator may decide whether submitted work is accepted;
        // the service applies the decision, state changes and rewards atomically.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $review = $this->service->creatorReview($id, is_array($body) ? $body : []);
        if (!$review) { $this->respond(409, false, 'Submission is not available for review'); return; }
        $this->respond(200, true, 'Review saved', ['submission' => $review]);
    }

    public function requestAppeal(): void
    {
        // A submitter may request one admin third-party check after creator rejection.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $appeal = $this->service->requestAppeal($id, is_array($body) ? $body : []);
        if (!$appeal) { $this->respond(409, false, 'Appeal is not allowed'); return; }
        $this->respond(201, true, 'Appeal requested', ['appeal' => $appeal]);
    }

    public function acceptRejection(): void
    {
        // Accepting a creator rejection makes it final and creates no reward transaction.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $submission = $this->service->acceptRejection($id, is_array($body) ? $body : []);
        if (!$submission) { $this->respond(409, false, 'Rejection cannot be accepted'); return; }
        $this->respond(200, true, 'Rejection accepted', ['submission' => $submission]);
    }

    public function appeals(): void
    {
        // Third-party checks are administrative overrides of creator rejections.
        // TODO [OPTIONAL]: add pagination to the admin queue if it grows.
        $id = $this->activeAdmin();
        if ($id === null) {
            return;
        }
        $this->respond(200, true, null, ['appeals' => $this->service->getAppealQueue($id)]);
    }

    public function appealView(): void
    {
        // Return only the appeal context permitted to the authenticated submitter,
        // task creator or active admin.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $appealId = filter_input(INPUT_GET, 'appeal_id', FILTER_VALIDATE_INT);
        $appeal = $appealId ? $this->service->getAppeal($id, $appealId) : null;
        if (!$appeal) { $this->respond(404, false, 'Appeal not found'); return; }
        $this->respond(200, true, null, ['appeal' => $appeal]);
    }

    public function reviewAppeal(): void
    {
        // Only an active admin may override or confirm a creator rejection.
        $id = $this->activeAdmin();
        if ($id === null) {
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $review = $this->service->reviewAppeal($id, is_array($body) ? $body : []);
        if (!$review) { $this->respond(409, false, 'Appeal is not available'); return; }
        $this->respond(200, true, 'Appeal reviewed', ['appeal' => $review]);
    }

    public function community(): void
    {
        // Community validation is secondary: it is offered only after completion
        // and excludes the creator, completer and prior reviewers.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $this->respond(200, true, null, ['submissions' => $this->service->getCommunityQueue($id)]);
    }

    public function communityView(): void
    {
        // TODO [OPTIONAL DETAIL]: The current community page renders the complete
        // queue rows directly; add a dedicated detail response if that UI changes.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $submissionId = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);
        $this->respond(200, true, null, ['submission_id' => $submissionId]);
    }

    public function communityReview(): void
    {
        // Record one independent yes/no opinion and award only the community
        // verification reward; completion state and completion points stay unchanged.
        $id = $this->activeUser();
        if ($id === null) {
            return;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $verification = $this->service->reviewCommunity($id, is_array($body) ? $body : []);
        if (!$verification) { $this->respond(409, false, 'Submission is not available'); return; }
        $this->respond(200, true, 'Community review saved', ['verification' => $verification]);
    }

    private function notImplemented(): void
    {
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not implemented']);
    }

    private function activeUser(): ?int
    {
        $id = Auth::userId();
        if ($id === null) {
            $this->respond(401, false, 'Not authenticated');
            return null;
        }
        $query = Database::connection()->prepare('SELECT status FROM users WHERE id = ?');
        $query->execute([$id]);
        $user = $query->fetch();
        if (!$user || $user['status'] !== 'active') {
            $this->respond(403, false, 'Account is not active');
            return null;
        }
        return $id;
    }
    private function activeAdmin(): ?int
    {
        $id = Auth::userId();
        if ($id === null) { $this->respond(401, false, 'Not authenticated'); return null; }
        $query = Database::connection()->prepare('SELECT status, role FROM users WHERE id = ?');
        $query->execute([$id]);
        $user = $query->fetch();
        if (!$user || $user['status'] !== 'active') { $this->respond(403, false, 'Account is not active'); return null; }
        if ($user['role'] !== 'admin') { $this->respond(403, false, 'Admin access required'); return null; }
        return $id;
    }
    private function respond(int $status, bool $success, ?string $message, array $data = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        $response = ['success' => $success];
        if ($message !== null) {
            $response['message'] = $message;
        }
        echo json_encode([...$response, ...$data]);
    }
}
