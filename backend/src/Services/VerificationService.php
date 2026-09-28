<?php

class VerificationService
{
    public function __construct(private PDO $db) {}

    public function getCreatorQueue(int $userId): array
    {
        // TODO [CREATOR VERIFICATION]: Join submissions to tasks where tasks.created_by=$userId and submissions.status=submitted.
        // Return evidence/image metadata for creator review; do not include finalized submissions.
        return [];
    }

    public function creatorReview(int $userId, array $input): ?array
    {
        // TODO [CREATOR VERIFICATION]: Validate submission_id, decision, comment, and task ownership.
        // Approved => submission verified/completed_at set, task completed, submitter gets task.points, competing submissions superseded.
        // Rejected => submission creator_rejected, task remains open, submitter gets 0 completion points.
        // Creator receives +10 for either decision exactly once. Use one transaction for state and point_transactions.
        return null;
    }

    public function requestAppeal(int $userId, array $input): ?array
    {
        // TODO [APPEAL]: Validate submission_id/reason and require submissions.user_id=$userId with status=creator_rejected.
        // Record one appeal and transition to appeal_pending. No second appeal and no points here.
        return null;
    }

    public function acceptRejection(int $userId, array $input): ?array
    {
        // TODO [APPEAL]: Require submission owner and creator_rejected state; transition to final rejected.
        // Preserve the rejection/acceptance record using the available design, make repeats safe, and award 0 points.
        return null;
    }

    public function getAppealQueue(int $userId): array
    {
        // TODO [APPEAL]: Return pending appeal records with submission/task/evidence context for an active eligible verifier.
        // Exclude task creator and submission creator; support validated filters/pagination.
        return [];
    }

    public function reviewAppeal(int $userId, array $input): ?array
    {
        // TODO [APPEAL]: Validate appeal_id, decision, comment, active reviewer, and appeal_pending state.
        // Reviewer cannot be task creator or submission creator and receives 0 points.
        // Approve => submission verified, task completed, submitter gets task.points, competing submissions superseded.
        // Reject => submission rejected and task remains open. Commit state/reward atomically; prohibit second appeal.
        return null;
    }

    public function getCommunityQueue(int $userId): array
    {
        // TODO [COMMUNITY VERIFICATION]: Return only submissions.status=verified for tasks.status=completed.
        // Exclude task creator, submission creator, and existing verifications for this user/type; different users remain eligible.
        return [];
    }

    public function reviewCommunity(int $userId, array $input): ?array
    {
        // TODO [COMMUNITY VERIFICATION]: Validate submission_id, decision=Yes/No, and optional comment.
        // Insert one community verifications row using UNIQUE(submission_id,verifier_id,verification_type).
        // Award +5 for either decision once. Never alter task/submission status or completion points; use a transaction.
        return null;
    }
}
