<?php

// --- Activity backend specification ---
// Backend scaffold/specification: Toon van Berkel
// Feature UI: Efe (feat-activity)
// Backend implementation: not yet implemented
// Activity returns the authenticated user's transaction history and calculated balance.

class ActivityController
{
    public function __construct()
    {
        $this->service = new PointsService(Database::connection());
    }

    private PointsService $service;

    public function index(): void
    {
        // The session user ID keeps the ledger private and prevents client-controlled
        // account lookups. TODO [OPTIONAL]: add pagination and explicit active-status handling.
        $userId = Auth::userId();
        if ($userId === null) {
            $this->respond(401, false, 'Not authenticated');
            return;
        }

        $activity = $this->service->getUserActivity($userId);
        $balance = $this->service->getUserBalance($userId);

        $this->respond(200, true, null, [
            'activity' => $activity,
            'balance' => $balance,
        ]);
    }

    private function respond(int $status, bool $success, ?string $message, array $data = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        $response = ['success' => $success];
        if ($message !== null) $response['message'] = $message;
        echo json_encode([...$response, ...$data]);
    }
}
