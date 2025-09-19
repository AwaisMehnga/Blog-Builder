<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

class SecurityHeadersMiddleware implements Middleware
{
    /**
     * Handle an incoming request and add comprehensive security headers.
     *
     * @param Request $request
     * @param \Closure $next
     * @param mixed ...$params
     * @return Response
     */
    public function handle(Request $request, \Closure $next, ...$params): Response
    {
        $response = $next($request);

        // Comprehensive security headers
        $securityHeaders = [
            // Prevent search engine indexing
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex',
            
            // Frame protection
            'X-Frame-Options' => 'DENY',
            
            // Content type protection
            'X-Content-Type-Options' => 'nosniff',
            
            // XSS protection
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer policy
            'Referrer-Policy' => 'no-referrer',
            
            // Cache control for sensitive pages
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            
            // HTTPS enforcement (if using HTTPS)
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            
            // Content Security Policy (environment-aware)
            'Content-Security-Policy' => $this->buildContentSecurityPolicy(),
            
            // Permission policy
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), fullscreen=()',
        ];

        // Apply all security headers
        foreach ($securityHeaders as $header => $value) {
            $response->setHeader($header, $value);
        }

        return $response;
    }

    /**
     * Build Content Security Policy based on environment
     */
    private function buildContentSecurityPolicy(): string
    {
        $appEnv = env('APP_ENV', 'production');
        $isDevelopment = in_array($appEnv, ['development', 'local']);
        
        // CDN domains used by VvvebBuilder and other components
        $cdnDomains = 'https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://via.placeholder.com https://cdn.tailwindcss.com https://code.jquery.com';
        
        if ($isDevelopment) {
            // Development CSP - allows Vite dev server and CDN resources
            return "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 ws://localhost:5173 {$cdnDomains}; " .
                   "style-src 'self' 'unsafe-inline' http://localhost:5173 {$cdnDomains}; " .
                   "connect-src 'self' http://localhost:5173 ws://localhost:5173; " .
                   "img-src 'self' data: http://localhost:5173 https:; " .
                   "font-src 'self' {$cdnDomains}; " .
                   "frame-ancestors 'none';";
        } else {
            // Production CSP - strict security (no CDNs in production for security)
            return "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline'; " .
                   "style-src 'self' 'unsafe-inline'; " .
                   "img-src 'self' data:; " .
                   "frame-ancestors 'none';";
        }
    }
}
