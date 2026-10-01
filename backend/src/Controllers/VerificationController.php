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
        // TODO [CREATOR QUEUE] (GET /verifications/creator): Require an active user who owns the relevant tasks.
        // Call getCreatorQueue() and return submitted submissions awaiting creator review, with evidence metadata.
        // Return 401/403 for auth/ownership failures and never include already-finalized submissions.
        $this->notImplemented();
    }

    public function creatorReview(): void
    {
        // TODO [CREATOR VERIFICATION] (POST /verifications/creator/review): Accept submission_id, decision (approved/rejected), comment.
        // Require the authenticated user to be the task creator; validate the current submitted state.
        // Approved: creator +10 once, submission verified, completed_at set, task completed, submitter gets task.points,
        // and competing submissions become superseded. Rejected: creator still +10 once, submission creator_rejected,
        // task remains open, and submitter gets 0 completion points. Commit state and rewards atomically.
        $this->notImplemented();
    }

    public function requestAppeal(): void
    {
        // TODO [APPEAL] (POST /appeals/request): Require the submission creator and accept submission_id plus appeal reason.
        // Allow only a creator-rejected submission, create one appeal, and transition it to appeal_pending.
        // Reject a second appeal or an already-final state; return 409 for duplicate/conflicting state.
        $this->notImplemented();
    }

    public function acceptRejection(): void
    {
        // TODO [APPEAL] (POST /appeals/accept-rejection): Require the submission creator and accept submission_id.
        // Allow only creator_rejected; transition to final rejected. This is terminal, creates no point transaction,
        // and must be idempotent or return a clear 409 for an already-resolved appeal.
        $this->notImplemented();
    }

    public function appeals(): void
    {
        // TODO [APPEAL] (GET /appeals): Require an active appeal verifier and return pending appeal summaries with pagination.
        // Reviewer cannot be task creator or submission creator; use 401/403 appropriately.
        $this->notImplemented();
    }

    public function appealView(): void
    {
        // TODO [APPEAL] (GET /appeals/view): Read appeal_id from query input and authorize the submitter, task creator,
        // or eligible appeal verifier. Return evidence, rejection history, and appeal state without unrelated private data.
        $this->notImplemented();
    }

    public function reviewAppeal(): void
    {
        // TODO [APPEAL] (POST /appeals/review): Accept appeal_id, decision (approved/rejected), and comment.
        // Require an active verifier who is neither task creator nor submission creator; allow no second appeal/review.
        // Approve: submission verified, task completed, submitter receives task.points, competing submissions superseded.
        // Reject: submission rejected and task remains open. Appeal verifier receives 0 points. Commit atomically.
        $this->notImplemented();
    }

    public function community(): void
    {
        // TODO [COMMUNITY VERIFICATION] (GET /verifications/community): Require an active user and list only verified
        // submissions on completed tasks. Exclude task creator, submission creator, and submissions already reviewed by this user.
        $this->notImplemented();
    }

    public function communityView(): void
    {
        // TODO [COMMUNITY VERIFICATION] (GET /verifications/community/view): Read submission_id and return eligible evidence.
        // Require active user, exclude task/submission creator, and return 403/409 when this user already reviewed it.
        $this->notImplemented();
    }

    public function communityReview(): void
    {
        // TODO [COMMUNITY VERIFICATION] (POST /verifications/community/review): Accept submission_id, decision (Yes/No), comment.
        // Require active eligible user and UNIQUE(submission_id,verifier_id,verification_type) protection.
        // Award +5 for either decision once. This adds trust data only: do not alter task/submission status or completion points.
        $this->notImplemented();
    }

    private function notImplemented(): void
    {
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not implemented']);
    }
}
