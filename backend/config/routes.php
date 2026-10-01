<?php

/**
 * Maps HTTP method + backend path to a controller method.
 *
 * Production requests use /api/auth/login, but htdocs/api/index.php removes
 * /api before the backend starts. The router therefore matches
 * POST /auth/login here, not POST /api/auth/login.
 */
return [
    'GET /health' => [HealthController::class, 'show'],

    'POST /auth/register' => [AuthController::class, 'register'],
    'POST /auth/login' => [AuthController::class, 'login'],
    'GET /auth/me' => [AuthController::class, 'me'],
    'POST /auth/logout' => [AuthController::class, 'logout'],

    'GET /tasks' => [TaskController::class, 'list'],
    'GET /tasks/view' => [TaskController::class, 'view'],
    'GET /tasks/mine' => [TaskController::class, 'mine'],
    'POST /tasks/create' => [TaskController::class, 'create'],
    
    'GET /submissions/view' => [SubmissionController::class, 'view'],
    'GET /submissions/mine' => [SubmissionController::class, 'mine'],
    'POST /submissions/create' => [SubmissionController::class, 'create'],
    
    'GET /verifications/creator' => [VerificationController::class, 'creator'],
    'POST /verifications/creator/review' => [VerificationController::class, 'creatorReview'],
    
    'POST /appeals/request' => [VerificationController::class, 'requestAppeal'],
    'POST /appeals/accept-rejection' => [VerificationController::class, 'acceptRejection'],
    'GET /appeals' => [VerificationController::class, 'appeals'],
    'GET /appeals/view' => [VerificationController::class, 'appealView'],
    'POST /appeals/review' => [VerificationController::class, 'reviewAppeal'],
    
    'GET /verifications/community' => [VerificationController::class, 'community'],
    'GET /verifications/community/view' => [VerificationController::class, 'communityView'],
    'POST /verifications/community/review' => [VerificationController::class, 'communityReview'],
    
    'GET /activity' => [ActivityController::class, 'index'],
    
    'GET /admin' => [AdminController::class, 'index'],
    'GET /admin/tasks' => [AdminController::class, 'tasks'],
    'GET /admin/tasks/view' => [AdminController::class, 'taskView'],
    'POST /admin/tasks/approve' => [AdminController::class, 'approveTask'],
    'POST /admin/tasks/reject' => [AdminController::class, 'rejectTask'],
];
