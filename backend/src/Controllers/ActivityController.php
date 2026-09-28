<?php

class ActivityController
{
    public function __construct()
    {
        $this->service = new PointsService(Database::connection());
    }

    private PointsService $service;

    public function index(): void
    {
        // TODO [ACTIVITY] (GET /activity): Require an active user and call PointsService::getUserActivity()/getUserBalance()
        // using the session user ID. Return point_transactions plus SUM(point_transactions.amount), with pagination and stable order.
        // Use 401/403 for auth/account failures; never expose another user's ledger or maintain a separate mutable balance.
        http_response_code(501);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not implemented']);
    }
}
