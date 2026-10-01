<?php

// --- Points backend specification ---
// Backend scaffold/specification: Toon van Berkel
// Point transaction rules below are part of the original backend design.

class PointsService
{
    public function __construct(private PDO $db) {}

    public function awardTaskCreation(int $userId, int $taskId): ?array
    {
        // TODO [FUTURE REFACTOR]: Reward writes currently happen inside the
        // admin approval transaction that owns the task state change.
        return null;
    }

    public function awardTaskCompletion(int $userId, int $submissionId): ?array
    {
        // TODO [FUTURE REFACTOR]: Reward writes currently happen inside the
        // verification transaction that completes the submission and task.
        return null;
    }

    public function awardCreatorVerification(int $userId, int $verificationId): ?array
    {
        // TODO [FUTURE REFACTOR]: Creator rewards are written inside the creator
        // review transaction so the decision and reward remain atomic.
        return null;
    }

    public function awardCommunityVerification(int $userId, int $verificationId): ?array
    {
        // TODO [FUTURE REFACTOR]: Community rewards are written inside the
        // community review transaction and are protected by verification identity.
        return null;
    }

    public function getUserActivity(int $userId): array
    {
        // Point transactions are the activity ledger and are returned newest first.
        // TODO [OPTIONAL]: Add pagination if activity history grows beyond demo scope.
        // Point transactions are the balance history and are shown newest first
        // so Activity puts the user's latest rewards at the top.
        $statement = $this->db->prepare(
            'SELECT id, task_id, submission_id, verification_id, amount, type, created_at
             FROM point_transactions
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function getUserBalance(int $userId): int
    {
        // The balance is calculated from the transaction ledger rather than stored
        // as a mutable users column.
        // The ledger is the only balance source of truth; no mutable balance is
        // stored separately on users.
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS balance
             FROM point_transactions
             WHERE user_id = ?'
        );
        $statement->execute([$userId]);
        return (int) ($statement->fetchColumn() ?? 0);
    }
}
