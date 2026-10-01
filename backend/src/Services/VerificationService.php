<?php

class VerificationService
{
    public function __construct(private PDO $db) {}

    public function getCreatorQueue(int $userId): array
    {
        // Return only submissions awaiting a decision from this task creator.
        $query = $this->db->prepare(
            "SELECT submissions.id AS submission_id, submissions.task_id, submissions.user_id,
                    submissions.status, submissions.evidence_description, submissions.submitted_at,
                    tasks.title, tasks.location_description
             FROM submissions JOIN tasks ON tasks.id = submissions.task_id
             WHERE tasks.created_by = ? AND submissions.status = 'submitted'
             ORDER BY submissions.submitted_at ASC"
        );
        $query->execute([$userId]);
        $rows = $query->fetchAll();
        foreach ($rows as &$row) {
            $images = $this->db->prepare(
                'SELECT id, image_url, original_filename, description, created_at
                 FROM submission_images WHERE submission_id = ?'
            );
            $images->execute([$row['submission_id']]);
            $row['images'] = $images->fetchAll();
        }
        return $rows;
    }

    public function creatorReview(int $userId, array $input): ?array
    {
        // The creator's decision is recorded atomically with task state and the
        // applicable rewards; competing submissions cannot complete a finished task.
        // Validate the decision before opening a transaction or changing any state.
        $submissionId = filter_var($input['submission_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $input['decision'] ?? '';
        if (!$submissionId || !in_array($decision, ['approved', 'rejected'], true)) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            // Load task ownership and reward data together with the submission.
            $query = $this->db->prepare(
                'SELECT submissions.*, tasks.created_by, tasks.points
                 FROM submissions JOIN tasks ON tasks.id = submissions.task_id
                 WHERE submissions.id = ? FOR UPDATE'
            );
            $query->execute([$submissionId]);
            $row = $query->fetch();
            // Only the task creator may decide whether this submitted work is
            // accepted while it is still awaiting review.
            if (!$row || $row['created_by'] != $userId || $row['status'] !== 'submitted') {
                $this->db->rollBack();
                return null;
            }

            // Store the creator decision as an auditable verification record
            // before applying its resulting state and reward changes.
            $verification = $this->db->prepare(
                'INSERT INTO verifications (submission_id, verifier_id, verification_type, decision, comment)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $verification->execute([$submissionId, $userId, 'creator', $decision, $input['comment'] ?? null]);
            $verificationId = (int) $this->db->lastInsertId();
            $newStatus = $decision === 'approved' ? 'verified' : 'creator_rejected';
            $this->db->prepare('UPDATE submissions SET status = ?, completed_at = ? WHERE id = ?')
                ->execute([$newStatus, $decision === 'approved' ? date('Y-m-d H:i:s') : null, $submissionId]);

            // Approval completes the task and makes competing submissions
            // obsolete so the same task cannot pay twice.
            if ($decision === 'approved') {
                $this->db->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND status = 'open'")
                    ->execute([$row['task_id']]);
                $this->db->prepare(
                    "UPDATE submissions SET status = 'superseded'
                     WHERE task_id = ? AND id <> ?
                     AND status IN ('submitted', 'creator_rejected', 'appeal_pending')"
                )->execute([$row['task_id'], $submissionId]);
                $this->db->prepare(
                    "INSERT INTO point_transactions (user_id, submission_id, amount, type)
                     VALUES (?, ?, ?, 'task_completed')"
                )->execute([$row['user_id'], $submissionId, (int) $row['points']]);
            }

            // The creator receives the specified review reward exactly once.
            $this->db->prepare(
                "INSERT INTO point_transactions (user_id, verification_id, amount, type)
                 VALUES (?, ?, 10, 'creator_verification')"
            )->execute([$userId, $verificationId]);
            // Commit only after the verification, state changes and rewards succeeded together.
            $this->db->commit();
            return ['submission_id' => $submissionId, 'status' => $newStatus];
        } catch (Throwable $exception) {
            // Never leave a partial review or reward behind after an error.
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

    public function requestAppeal(int $userId, array $input): ?array
    {
        // A submitter may request a third-party check only after creator rejection.
        // Record one appeal and transition to appeal_pending. No second appeal and no points here.
        $submissionId = filter_var($input['submission_id'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim($input['reason'] ?? '');
        if (!$submissionId || $reason === '') {
            return null;
        }
        // Only the submitter may request a third-party check for their own creator rejection.
        $query = $this->db->prepare('SELECT id, user_id, status FROM submissions WHERE id = ?');
        $query->execute([$submissionId]);
        $row = $query->fetch();
        if (!$row || $row['user_id'] != $userId || $row['status'] !== 'creator_rejected') {
            return null;
        }
        try {
            // Create the pending check before moving the submission into appeal_pending.
            $appeal = $this->db->prepare(
                "INSERT INTO appeals (submission_id, requested_by, reason, status)
                 VALUES (?, ?, ?, 'pending')"
            );
            $appeal->execute([$submissionId, $userId, $reason]);
            $appealId = (int) $this->db->lastInsertId();
            $this->db->prepare("UPDATE submissions SET status = 'appeal_pending' WHERE id = ?")
                ->execute([$submissionId]);
            return ['id' => $appealId, 'submission_id' => $submissionId, 'status' => 'pending'];
        } catch (PDOException) {
            return null;
        }
    }

    public function acceptRejection(int $userId, array $input): ?array
    {
        // Accepting the rejection makes it final and awards no completion points.
        // Preserve the rejection/acceptance record using the available design, make repeats safe, and award 0 points.
        $submissionId = filter_var($input['submission_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$submissionId) {
            return null;
        }
        $query = $this->db->prepare(
            "UPDATE submissions SET status = 'rejected'
             WHERE id = ? AND user_id = ? AND status = 'creator_rejected'"
        );
        $query->execute([$submissionId, $userId]);
        return $query->rowCount() === 1 ? ['submission_id' => $submissionId, 'status' => 'rejected'] : null;
    }

    private function submissionImages(int $submissionId): array
    {
        $query = $this->db->prepare(
            'SELECT id, image_url, original_filename, description, created_at
             FROM submission_images WHERE submission_id = ?'
        );
        $query->execute([$submissionId]);
        return $query->fetchAll();
    }

    public function getAppealQueue(int $userId): array
    {
        // Third-party checks are queued for active admins with the evidence and
        // task context needed to review the creator's rejection.
        // Exclude task creator and submission creator; support validated filters/pagination.
        // The controller restricts this queue to admins; the query supplies the review context.
        $query = $this->db->prepare(
            "SELECT appeals.id AS appeal_id, appeals.submission_id, appeals.reason, appeals.status,
                    appeals.created_at, submissions.user_id AS submitter_id,
                    submissions.evidence_description, tasks.created_by, tasks.title,
                    tasks.location_description
             FROM appeals JOIN submissions ON submissions.id = appeals.submission_id
             JOIN tasks ON tasks.id = submissions.task_id
             WHERE appeals.status = 'pending' AND submissions.status = 'appeal_pending'
               AND tasks.created_by <> ? AND submissions.user_id <> ?
             ORDER BY appeals.created_at ASC"
        );
        $query->execute([$userId, $userId]);
        $rows = $query->fetchAll();
        foreach ($rows as &$row) $row['images'] = $this->submissionImages((int) $row['submission_id']);
        return $rows;
    }

    public function getAppeal(int $userId, int $appealId): ?array
    {
        $query = $this->db->prepare(
            "SELECT appeals.id AS appeal_id, appeals.submission_id, appeals.reason, appeals.status,
                    appeals.decision, appeals.created_at, appeals.reviewed_at,
                    submissions.user_id AS submitter_id, submissions.status AS submission_status,
                    submissions.evidence_description, tasks.id AS task_id, tasks.title,
                    tasks.location_description, tasks.created_by
             FROM appeals JOIN submissions ON submissions.id = appeals.submission_id
             JOIN tasks ON tasks.id = submissions.task_id
             WHERE appeals.id = ? AND (submissions.user_id = ? OR tasks.created_by = ?
                    OR (tasks.created_by <> ? AND submissions.user_id <> ?))"
        );
        $query->execute([$appealId, $userId, $userId, $userId, $userId]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }
        $row['images'] = $this->submissionImages((int) $row['submission_id']);
        $history = $this->db->prepare(
            "SELECT verifier_id, decision, comment, created_at FROM verifications
             WHERE submission_id = ? AND verification_type = 'creator' ORDER BY id DESC"
        );
        $history->execute([$row['submission_id']]);
        $row['creator_verifications'] = $history->fetchAll();
        return $row;
    }

    public function reviewAppeal(int $userId, array $input): ?array
    {
        // Only an active admin may override or confirm a creator rejection.
        // Reviewer cannot be task creator or submission creator and receives 0 points.
        // Approve => submission verified, task completed, submitter gets task.points, competing submissions superseded.
        // Reject => submission rejected and task remains open. Commit state/reward atomically; prohibit second appeal.
        $appealId = filter_var($input['appeal_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $input['decision'] ?? '';
        if (!$appealId || !in_array($decision, ['approved', 'rejected'], true)) {
            return null;
        }
        // This is a creator-rejection override, so only the admin-authorized controller may call it.
        // A third-party check can override a creator rejection, so only an
        // active admin may make this decision.
        $this->db->beginTransaction();
        try {
            // Lock the appeal and load the task/submission needed for ownership and reward checks.
            $query = $this->db->prepare(
                'SELECT appeals.*, submissions.task_id, submissions.user_id, tasks.created_by, tasks.points
                 FROM appeals JOIN submissions ON submissions.id = appeals.submission_id
                 JOIN tasks ON tasks.id = submissions.task_id WHERE appeals.id = ? FOR UPDATE'
            );
            $query->execute([$appealId]);
            $appeal = $query->fetch();
            if (!$appeal || $appeal['status'] !== 'pending'
                || $appeal['user_id'] == $userId || $appeal['created_by'] == $userId) {
                $this->db->rollBack();
                return null;
            }
            // Keep the admin decision in verification history for auditability.
            $verification = $this->db->prepare(
                'INSERT INTO verifications (submission_id, verifier_id, verification_type, decision, comment)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $verification->execute([
                $appeal['submission_id'], $userId, 'appeal', $decision, $input['comment'] ?? null,
            ]);
            $newStatus = $decision === 'approved' ? 'verified' : 'rejected';
            $this->db->prepare(
                'UPDATE appeals SET status = ?, reviewed_by = ?, decision = ?, reviewed_at = NOW() WHERE id = ?'
            )->execute([$newStatus, $userId, $decision, $appealId]);
            $this->db->prepare('UPDATE submissions SET status = ?, completed_at = ? WHERE id = ?')
                ->execute([$newStatus, $decision === 'approved' ? date('Y-m-d H:i:s') : null, $appeal['submission_id']]);
            // An approved third-party check overrides the creator rejection and completes the task.
            // The submitter receives the normal completion reward; the admin receives no reward.
            if ($decision === 'approved') {
                $this->db->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND status = 'open'")
                    ->execute([$appeal['task_id']]);
                $this->db->prepare(
                    "UPDATE submissions SET status = 'superseded' WHERE task_id = ? AND id <> ?
                     AND status IN ('submitted', 'creator_rejected', 'appeal_pending')"
                )->execute([$appeal['task_id'], $appeal['submission_id']]);
                $this->db->prepare(
                    "INSERT INTO point_transactions (user_id, submission_id, amount, type)
                     VALUES (?, ?, ?, 'task_completed')"
                )->execute([$appeal['user_id'], $appeal['submission_id'], (int) $appeal['points']]);
            }
            // Do not expose a partially applied override if any state or reward update failed.
            $this->db->commit();
            return ['appeal_id' => $appealId, 'status' => $newStatus];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

    public function getCommunityQueue(int $userId): array
    {
        // Community validation is available only after the task is already completed.
        // TODO [OPTIONAL]: Add history/counts or pagination if the queue grows.
        // Exclude task creator, submission creator, and existing verifications for this user/type; different users remain eligible.
        // Community validation is secondary: only already-completed work is eligible here.
        $query = $this->db->prepare(
            "SELECT submissions.id AS submission_id, submissions.task_id, submissions.user_id,
                    submissions.status, submissions.evidence_description, tasks.title,
                    tasks.location_description
             FROM submissions JOIN tasks ON tasks.id = submissions.task_id
             WHERE submissions.status = 'verified' AND tasks.status = 'completed'
               AND tasks.created_by <> ? AND submissions.user_id <> ?
               AND NOT EXISTS (SELECT 1 FROM verifications v WHERE v.submission_id = submissions.id
                    AND v.verifier_id = ? AND v.verification_type = 'community')
             ORDER BY submissions.completed_at DESC"
        );
        $query->execute([$userId, $userId, $userId]);
        $rows = $query->fetchAll();
        foreach ($rows as &$row) $row['images'] = $this->submissionImages((int) $row['submission_id']);
        return $rows;
    }

    public function reviewCommunity(int $userId, array $input): ?array
    {
        // Each eligible community member records one independent yes/no opinion.
        // This never changes completion state or pays the task completion reward again.
        // Insert one community verifications row using UNIQUE(submission_id,verifier_id,verification_type).
        // Award +5 for either decision once. Never alter task/submission status or completion points; use a transaction.
        $submissionId = filter_var($input['submission_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $input['decision'] ?? '';
        if (!$submissionId || !in_array($decision, ['approved', 'rejected'], true)) {
            return null;
        }
        // Load the completed submission so the eligibility rules can be enforced server-side.
        $query = $this->db->prepare(
            "SELECT submissions.user_id, tasks.created_by FROM submissions
             JOIN tasks ON tasks.id = submissions.task_id
             WHERE submissions.id = ? AND submissions.status = 'verified' AND tasks.status = 'completed'"
        );
        $query->execute([$submissionId]);
        $row = $query->fetch();
        if (!$row || $row['user_id'] == $userId || $row['created_by'] == $userId) {
            return null;
        }
        try {
        // Community validation happens after completion. It records an
        // independent opinion and must never complete or reward the task again.
        $this->db->beginTransaction();
            // Record an independent yes/no opinion without changing completion state.
            $verification = $this->db->prepare(
                'INSERT INTO verifications (submission_id, verifier_id, verification_type, decision, comment)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $verification->execute([
                $submissionId, $userId, 'community', $decision, $input['comment'] ?? null,
            ]);
            $verificationId = (int) $this->db->lastInsertId();
            // Community reward is separate from the task completion reward and is awarded once per verifier.
            $this->db->prepare(
                "INSERT INTO point_transactions (user_id, verification_id, amount, type)
                 VALUES (?, ?, 5, 'community_verification')"
            )->execute([$userId, $verificationId]);
            $this->db->commit();
            return ['submission_id' => $submissionId, 'decision' => $decision];
        } catch (Throwable) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return null;
        }
    }
}
