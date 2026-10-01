<?php

class PointsService
{
    public function __construct(private PDO $db) {}

    public function awardTaskCreation(int $userId, int $taskId): ?array
    {
        // TODO [POINTS]: Create one point_transactions row with type=task_created and amount=+20 only after task approval.
        // Require approved/open task context, use task_id as the idempotency key, and never award twice on retries.
        return null;
    }

    public function awardTaskCompletion(int $userId, int $submissionId): ?array
    {
        // TODO [POINTS]: Create type=task_completed for amount=tasks.points after verified completion.
        // Use submission_id for idempotency and commit completion state plus reward atomically.
        return null;
    }

    public function awardCreatorVerification(int $userId, int $verificationId): ?array
    {
        // TODO [POINTS]: Create type=creator_verification with amount=+10 for either creator decision exactly once.
        // Link verification_id and reject duplicate reward attempts without a second ledger row.
        return null;
    }

    public function awardCommunityVerification(int $userId, int $verificationId): ?array
    {
        // TODO [POINTS]: Create type=community_verification with amount=+5 for either community decision exactly once.
        // Link verification_id; community review never changes task/submission status. Appeal verification earns 0.
        return null;
    }

    public function getUserActivity(int $userId): array
    {
        // TODO [ACTIVITY]: Return point_transactions for user_id=$userId, newest first, with type/amount and source IDs.
        // Include pagination and never synthesize or mutate a separate balance column.
        return [];
    }

    public function getUserBalance(int $userId): int
    {
        // TODO [POINTS]: Calculate balance exclusively as SUM(point_transactions.amount) for user_id=$userId.
        // Rules: task_created +20, creator_verification +10, task_completed tasks.points, community_verification +5,
        // submitting 0, appeal verification 0. Do not add a mutable balance field.
        return 0;
    }
}
