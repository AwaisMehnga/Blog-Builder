<?php

namespace Core;

use Closure;

interface Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$params
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$params): mixed;
}
