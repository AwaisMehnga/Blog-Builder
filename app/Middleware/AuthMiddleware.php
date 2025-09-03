<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

class AuthMiddleware implements Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param mixed ...$params
     * @return Response
     */
    public function handle(Request $request, \Closure $next, ...$params): Response
    {
        if (!auth()->check()) {
            // Return a Response object directly
            return (new Response())->setStatus(403)->setContent('Unauthorized');
        }

        return $next($request);
    }
}
