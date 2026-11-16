<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = app()->environment('local', 'testing')
            || (app()->environment('staging') && config('demo.data_allowed'));

        abort_unless($allowed, 403, 'API data demo tidak aktif pada lingkungan ini.');

        return $next($request);
    }
}
