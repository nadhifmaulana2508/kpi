<?php

namespace App\Http\Middleware;

use App\Models\AuditEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordAuthenticatedActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $route = $request->route();
        $routePath = $route?->uri() ?? '';
        $isContractEndpoint = preg_match('~(^|/)contracts(?:/|$)~', $routePath) === 1;

        if (
            ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || ! $response->isSuccessful()
            || $request->user() === null
            || str_ends_with($routePath, 'audit-events/menu-access')
            || $isContractEndpoint
        ) {
            return $response;
        }

        try {
            AuditEvent::query()->create([
                'actor_employee_id' => $request->user()->getAuthIdentifier(),
                'action' => 'API_ACTION',
                'subject_type' => 'API_ENDPOINT',
                'subject_id' => $routePath,
                'after_state' => [
                    'method' => $request->method(),
                    'route_name' => $route?->getName(),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
