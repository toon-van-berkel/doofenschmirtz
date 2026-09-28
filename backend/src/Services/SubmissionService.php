<?php

class SubmissionService
{
    public function __construct(private PDO $db) {}

    public function createSubmission(int $userId, array $input): ?array
    {
        // TODO [SUBMISSION]: Validate task_id and evidence_description; task must exist with status=open.
        // Reject when tasks.created_by=$userId, enforce UNIQUE(submissions.task_id,submissions.user_id), and store evidence metadata.
        // Final implementation requires evidence images in submission_images. Insert status=submitted and no points.
        // Use a transaction for submission plus image metadata; clean newly stored files if the transaction fails.
        return null;
    }

    public function getSubmission(int $submissionId, int $userId): ?array
    {
        // TODO [SUBMISSION]: Return status, evidence fields, timestamps, and submission_images only when $userId is
        // submitter, task creator, or an authorized verifier; never expose unrelated private submissions.
        return null;
    }

    public function getSubmissionsByUser(int $userId): array
    {
        // TODO [MY SUBMISSIONS]: Filter submissions.user_id=$userId and return task summary, status, timestamps,
        // evidence metadata, and verification/appeal state for that user only.
        return [];
    }
}
