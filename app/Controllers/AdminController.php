<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

class AdminController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_COOLDOWN = 300; // 5 minutes
    private const SESSION_TIMEOUT = 3600; // 1 hour

    /**
     * Destroy admin session
     */
    private function destroySession(): void
    {
        unset($_SESSION['admin_authenticated']);
        unset($_SESSION['admin_last_activity']);
        unset($_SESSION['csrf_token']);
    }

    /**
     * Check if admin is authenticated via session
     */
    private function isAuthenticated(): bool
    {
        if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['admin_last_activity'])) {
            if (time() - $_SESSION['admin_last_activity'] > self::SESSION_TIMEOUT) {
                $this->destroySession();
                return false;
            }
        }

        // Update last activity
        $_SESSION['admin_last_activity'] = time();
        
        return true;
    }

    /**
     * Generate CSRF token
     */
    private function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    private function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Check if IP is rate limited
     */
    private function isRateLimited(string $ip): bool
    {
        $key = 'login_attempts_' . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }

        $attempts = $_SESSION[$key];
        
        // Reset attempts if cooldown period has passed
        if (time() - $attempts['last_attempt'] > self::LOGIN_COOLDOWN) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
            return false;
        }

        return $attempts['count'] >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(string $ip): void
    {
        $key = 'login_attempts_' . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => 0];
        }

        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
    }

    /**
     * Reset login attempts
     */
    private function resetLoginAttempts(string $ip): void
    {
        $key = 'login_attempts_' . $ip;
        unset($_SESSION[$key]);
    }

    /**
     * Authenticate admin with environment credentials (timing-safe)
     */
    private function authenticate(string $username, string $password): bool
    {
        $adminUsername = env('ADMIN_USERNAME');
        $adminPassword = env('ADMIN_PASSWORD');
        
        // Use hash_equals for timing-safe comparison
        $usernameValid = hash_equals($adminUsername, $username);
        $passwordValid = hash_equals($adminPassword, $password);
        
        return $usernameValid && $passwordValid;
    }

    /**
     * Admin login page and authentication
     */
    public function login(Request $request): Response
    {
        // If already authenticated, redirect to dashboard
        if ($this->isAuthenticated()) {
            return $this->redirect('/admin/awais-mehnga/dashboard');
        }

        $clientIp = $request->ip();

        // Check rate limiting
        if ($this->isRateLimited($clientIp)) {
            $remainingTime = self::LOGIN_COOLDOWN - (time() - $_SESSION['login_attempts_' . $clientIp]['last_attempt']);
            return $this->view('admin.login', [
                'error' => "Too many failed attempts. Please try again in " . ceil($remainingTime / 60) . " minutes.",
                'csrf_token' => $this->generateCsrfToken()
            ]);
        }

        // Handle login form submission
        if ($request->isPost()) {
            $username = $request->input('username', '');
            $password = $request->input('password', '');
            $csrfToken = $request->input('csrf_token', '');

            // Verify CSRF token
            if (!$this->verifyCsrfToken($csrfToken)) {
                return $this->view('admin.login', [
                    'error' => 'Invalid security token. Please try again.',
                    'csrf_token' => $this->generateCsrfToken()
                ]);
            }

            // Validate input
            if (empty($username) || empty($password)) {
                $this->recordFailedAttempt($clientIp);
                return $this->view('admin.login', [
                    'error' => 'Username and password are required.',
                    'csrf_token' => $this->generateCsrfToken()
                ]);
            }

            if ($this->authenticate($username, $password)) {
                // Reset failed attempts on successful login
                $this->resetLoginAttempts($clientIp);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set authentication session
                $_SESSION['admin_authenticated'] = true;
                $_SESSION['admin_last_activity'] = time();
                $_SESSION['admin_ip'] = $clientIp;
                
                // Generate new CSRF token
                unset($_SESSION['csrf_token']);
                
                return $this->redirect('/admin/awais-mehnga/dashboard');
            } else {
                // Record failed attempt
                $this->recordFailedAttempt($clientIp);
                
                return $this->view('admin.login', [
                    'error' => 'Invalid credentials.',
                    'csrf_token' => $this->generateCsrfToken()
                ]);
            }
        }

        // Show login form with security headers
        $response = $this->view('admin.login', [
            'csrf_token' => $this->generateCsrfToken()
        ]);
        
        // Add no-index headers to login page
        $response->setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'no-referrer');
        
        return $response;
    }

    /**
     * Admin dashboard - Fully protected and non-indexable
     */
    public function dashboard(Request $request): Response
    {
        // Double-check authentication
        if (!$this->isAuthenticated()) {
            return $this->redirect('/admin/awais-mehnga/login');
        }

        // Additional security: Verify session integrity
        if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
            $this->destroySession();
            return $this->redirect('/admin/awais-mehnga/login');
        }

        // Verify IP consistency for extra security
        if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== $request->ip()) {
            $this->destroySession();
            session_regenerate_id(true);
            return $this->redirect('/admin/awais-mehnga/login');
        }

        // Create response with security headers
        $response = $this->view('admin.dashboard');
        
        // Add security headers to prevent indexing and enhance protection
        $response->setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'no-referrer');
        $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');

        return $response;
    }

    /**
     * Admin logout
     */
    public function logout(Request $request): Response
    {
        // Destroy session data securely
        $this->destroySession();
        
        // Regenerate session ID
        session_regenerate_id(true);
        
        return $this->redirect('/admin/awais-mehnga');
    }

    /**
     * Handle the main admin route
     */
    public function index(Request $request): Response
    {
        // If already authenticated, redirect to dashboard
        if ($this->isAuthenticated()) {
            return $this->redirect('/admin/awais-mehnga/dashboard');
        }

        // Otherwise redirect to login
        return $this->redirect('/admin/awais-mehnga/login');
    }
}
