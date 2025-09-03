<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

class AdminMiddleware implements Middleware
{
    private const SESSION_TIMEOUT = 3600; // 1 hour

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
        // Check if admin is authenticated
        if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
            return Response::redirect('/admin/awais-mehnga/login');
        }

        // Check session timeout
        if (isset($_SESSION['admin_last_activity'])) {
            if (time() - $_SESSION['admin_last_activity'] > self::SESSION_TIMEOUT) {
                // Session expired
                unset($_SESSION['admin_authenticated']);
                unset($_SESSION['admin_last_activity']);
                unset($_SESSION['csrf_token']);
                session_regenerate_id(true);
                
                return Response::redirect('/admin/awais-mehnga/login');
            }
        }

        // Optional: Check if IP changed (additional security)
        if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== $request->ip()) {
            // IP changed, logout for security
            unset($_SESSION['admin_authenticated']);
            unset($_SESSION['admin_last_activity']);
            unset($_SESSION['admin_ip']);
            unset($_SESSION['csrf_token']);
            session_regenerate_id(true);
            
            return Response::redirect('/admin/awais-mehnga/login');
        }

        // Update last activity
        $_SESSION['admin_last_activity'] = time();

        // Continue to the next middleware/controller
        $response = $next($request);

        // Add comprehensive security headers to ALL admin responses
        $response->setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'no-referrer');
        $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');
        $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        return $response;
    }
}
