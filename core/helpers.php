<?php

use Core\App;
use Core\Auth;
use Core\Response;
use Core\Router;
use Core\Vite;
use Core\Email;

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        // First check $_ENV (populated by dotenv)
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // Fallback to getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return App::getInstance()->config($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * Render a PHP view template from views directory with given data.
     * Supports layout inheritance and partials via basic PHP includes.
     */
    function view(string $template, array $data = []): string
    {
        $viewPath = App::getInstance()->getBasePath() . '/app/Views/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($viewPath)) {
            throw new RuntimeException("View file not found: {$viewPath}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
}

if (!function_exists('response')) {
    /**
     * Create and send HTTP response.
     * Accepts string, array (json), or Core\Response instance.
     */
    function response(mixed $content = '', int $status = 200, array $headers = []): Response
    {
        if ($content instanceof Response) {
            return $content;
        }

        if (is_array($content)) {
            $content = json_encode($content);
            $headers['Content-Type'] = 'application/json';
        } elseif (is_object($content) && method_exists($content, '__toString')) {
            $content = (string) $content;
        } elseif (!is_string($content)) {
            $content = (string) $content;
        }

        $response = new Response($content, $status, $headers);
        return $response;
    }
}

if (!function_exists('auth')) {
    /**
     * Return singleton Auth instance.
     */
    function auth(): Auth
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new Auth();
        }
        return $instance;
    }
}

if (!function_exists('route')) {
    /**
     * Generate URL for a named route.
     * @param string $name Route name
     * @param array $params Parameters for route wildcards or query string
     * @return string URL
     */
    function route(string $name, array $params = []): string
    {
        $url = Router::getInstance()->generateUrl($name, $params);
        return $url;
    }
}

if (!function_exists('redirect')) {
    /**
     * Send an HTTP redirect to given URL with status code.
     */
    function redirect(string $url, int $status = 302): void
    {
        header("Location: {$url}", true, $status);
        exit;
    }
}

if (!function_exists('session')) {
    /**
     * Return session manager instance from App.
     * Assumes App class has session() method returning session handler.
     */
    function session()
    {
        return App::getInstance()->session();
    }
}

function view_path(string $name): string
{
    return App::getInstance()->getBasePath() . '/app/Views/' . str_replace('.', '/', $name) . '.php';
}

if (!function_exists('paginate_links')) {
    /**
     * Render pagination links HTML
     * 
     * @param array $pagination Pagination data from paginate() method
     * @param array $options Customization options
     * @return string HTML for pagination links
     */
    function paginate_links(array $pagination, array $options = []): string
    {
        if (empty($pagination['links'])) {
            return '';
        }

        $baseClass = $options['class'] ?? 'pagination';
        $linkClass = $options['link_class'] ?? 'page-link';
        $activeClass = $options['active_class'] ?? 'active';
        $disabledClass = $options['disabled_class'] ?? 'disabled';

        $html = "<nav class=\"{$baseClass}\">";

        foreach ($pagination['links'] as $link) {
            $classes = [$linkClass];

            if ($link['active']) {
                $classes[] = $activeClass;
            }

            if (!$link['url']) {
                $classes[] = $disabledClass;
            }

            $classStr = implode(' ', $classes);

            if ($link['url']) {
                $html .= "<a href=\"" . htmlspecialchars($link['url']) . "\" class=\"{$classStr}\">";
                $html .= $link['label'];
                $html .= "</a>";
            } else {
                $html .= "<span class=\"{$classStr}\">{$link['label']}</span>";
            }
        }

        $html .= "</nav>";

        return $html;
    }
}

if (!function_exists('pagination_info')) {
    /**
     * Generate pagination info text
     * 
     * @param array $pagination Pagination data from paginate() method
     * @return string Info text like "Showing 1 to 15 of 100 results"
     */
    function pagination_info(array $pagination): string
    {
        if ($pagination['total'] == 0) {
            return 'No results found';
        }

        return "Showing {$pagination['from']} to {$pagination['to']} of {$pagination['total']} results";
    }
}

if (!function_exists('send_email')) {
    /**
 * Send email using system SMTP or user-specific SMTP config
 *
 * @param array|null $smtpConfig Optional user SMTP config from DB
 * @param string $toEmail
 * @param string $toName
 * @param string $subject
 * @param string $body
 * @param string $altBody
 * @return bool|string True if sent, error message otherwise
 */
function send_email(array $smtpConfig = [], string $toEmail, string $toName, string $subject, string $body, string $altBody = '')
{
    // Load default SMTP from .env
    $defaultConfig = [
        'host'       => $_ENV['SMTP_HOST'] ?? 'smtp.example.com',
        'port'       => (int)($_ENV['SMTP_PORT'] ?? 587),
        'username'   => $_ENV['SMTP_USER'] ?? '',
        'password'   => $_ENV['SMTP_PASS'] ?? '',
        'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
        'fromEmail'  => $_ENV['SMTP_FROM_EMAIL'] ?? ($_ENV['SMTP_USER'] ?? ''),
        'fromName'   => $_ENV['SMTP_FROM_NAME'] ?? 'Mailer'
    ];

    // Merge user SMTP config if provided
    if ($smtpConfig) {
        $config = array_merge($defaultConfig, $smtpConfig);
    } else {
        $config = $defaultConfig;
    }

    $mailer = new Email(
        $config['host'],
        $config['port'],
        $config['username'],
        $config['password'],
        $config['encryption'],
        $config['fromEmail'],
        $config['fromName']
    );

    return $mailer->send($toEmail, $toName, $subject, $body, $altBody);
}
}


if (!function_exists('abort')) {
    /**
     * Abort the request with given HTTP status code and optional message.
     * Outputs minimal error and exits.
     */
    function abort(int $statusCode, string $message = ''): void
    {
        http_response_code($statusCode);
        if ($message === '') {
            $message = match ($statusCode) {
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                500 => 'Internal Server Error',
                default => 'Error',
            };
        }
        echo "<h1>{$statusCode} - {$message}</h1>";
        exit;
    }
}
if (!function_exists('vite_asset')) {

    function vite_asset(string $entry)
    {
        $vite = new Vite();
        return '<script type="module" src="' . $vite->asset($entry) . '"></script>';
    }
}

if (!function_exists('vite_css')) {
    function vite_css(string $entry)
    {
        $vite = new Vite();
        $tags = '';
        foreach ($vite->css($entry) as $css) {
            $baseUrl = $vite->isHot() ? $vite->hotUrl() : '/build';
            $tags .= '<link rel="stylesheet" href="' . $baseUrl . '/' . $css . '">';
        }
        return $tags;
    }
}


if (!function_exists('vite_react_refresh')) {
    function vite_react_refresh()
    {
        $vite = new Vite();
        if ($vite->isHot()) {
            return <<<HTML
            <script type="module">
            import RefreshRuntime from "{$vite->hotUrl()}/@react-refresh"
            RefreshRuntime.injectIntoGlobalHook(window)
            window.\$RefreshReg\$ = () => {}
            window.\$RefreshSig\$ = () => (type) => type
            window.__vite_plugin_react_preamble_installed__ = true
            </script>
            HTML;
        }
        return '';
    }
}
