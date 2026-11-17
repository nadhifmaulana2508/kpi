<?php

use App\Http\Controllers\Api\V1\AuditEventController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\ContractWorkflowController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DemoIdentityController;
use App\Http\Controllers\Api\V1\KpiCycleController;
use App\Http\Controllers\Api\V1\KpiDefinitionController;
use App\Http\Controllers\Api\V1\OrgUnitController;
use App\Http\Controllers\Api\V1\SsoLoginController;
use App\Http\Controllers\Api\V1\SsoIdentityAdminController;
use App\Http\Middleware\EnsureActiveEmployee;
use App\Http\Middleware\EnsureDemoEnvironment;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(EnsureDemoEnvironment::class)->group(function (): void {
    Route::get('/demo/identities', [DemoIdentityController::class, 'index']);
    Route::post('/demo/session', [DemoIdentityController::class, 'store']);
    Route::delete('/demo/session', [DemoIdentityController::class, 'destroy']);
    Route::post('/auth/sso/login', [SsoLoginController::class, 'store'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', EnsureActiveEmployee::class])->group(function (): void {
        Route::get('/me', CurrentUserController::class);
        Route::get('/sso-identities', [SsoIdentityAdminController::class, 'index']);
        Route::post('/sso-identities', [SsoIdentityAdminController::class, 'store']);
        Route::patch('/sso-identities/{identityId}', [SsoIdentityAdminController::class, 'update']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/cycles', [KpiCycleController::class, 'index']);
        Route::get('/org-units', [OrgUnitController::class, 'index']);
        Route::get('/kpi-definitions', [KpiDefinitionController::class, 'index']);
        Route::get('/kpi-definitions/{definition}', [KpiDefinitionController::class, 'show']);
        Route::get('/audit-events', [AuditEventController::class, 'index']);
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::post('/contracts', [ContractController::class, 'store']);
        Route::get('/contracts/{contract}', [ContractController::class, 'show']);
        Route::patch('/contracts/{contract}/items/{item}', [ContractWorkflowController::class, 'updateItem']);
        Route::post('/contracts/{contract}/comments', [ContractWorkflowController::class, 'comment']);
        Route::post('/contracts/{contract}/submit', [ContractWorkflowController::class, 'submit']);
        Route::post('/contracts/{contract}/reviews', [ContractWorkflowController::class, 'review']);
    });
});
