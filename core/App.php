<?php
namespace Core;

class App
{
    protected Router $router;
    protected Request $request;
    protected Response $response;
    protected array $routeFiles = [];
    protected array $globalMiddleware = [];
    protected array $bindings = [];
    protected static ?self $instance = null;
    protected array $config = [];
    protected string $basePath;

    public function __construct(array $routeFiles = [], array $globalMiddleware = [])
    {
        self::$instance = $this;

        // Load helpers first since config files depend on env() function
        $this->loadHelpers();
        $this->loadConfig();
        $this->request = new Request();
        $this->response = new Response();
        $this->router = Router::getInstance();
        $this->routeFiles = $routeFiles;
        $this->globalMiddleware = $globalMiddleware;
        // 
        $this->basePath = dirname(__DIR__);

        $this->startSessionIfNeeded();
        $this->registerDefaultBindings();
        $this->loadRoutes($this->routeFiles);
        $this->registerGlobalMiddlewareFromConfig();
        $this->bootstrapAuth();
    }

    protected function loadHelpers(): void
    {
        $helpersPath = dirname(__DIR__) . '/core/helpers.php';
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    protected function loadConfig(): void
    {
        $appConfig = [];
        $configPath = dirname(__DIR__) . '/config/app.php';
        if (file_exists($configPath)) {
            $appConfig = include $configPath;
        }
        $this->config = $appConfig;
    }

    protected function startSessionIfNeeded(): void
    {
        // Don't start session in CLI mode
        if (php_sapi_name() === 'cli') {
            return;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => 0,
                'cookie_httponly' => true,
                'use_strict_mode' => true,
                'use_only_cookies' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    protected function registerDefaultBindings(): void
    {
        $this->bind('router', fn() => $this->router);
        $this->bind('request', fn() => $this->request);
        $this->bind('response', fn() => $this->response);
        $this->bind('app', fn() => $this);
    }

    protected function registerGlobalMiddlewareFromConfig(): void
    {
        if (isset($this->config['middleware']) && is_array($this->config['middleware'])) {
            $this->globalMiddleware = array_merge($this->globalMiddleware, $this->config['middleware']);
        }
    }

    protected function bootstrapAuth(): void
    {
        // Ensure session is available; Auth class is expected to use session internally
        // No explicit wiring here beyond session start; guards can be configured externally
    }

    public function session(): object
    {
        // Return an anonymous class with proper methods
        return new class {
            public function get(string $key, mixed $default = null): mixed
            {
                return $_SESSION[$key] ?? $default;
            }
            
            public function put(string $key, mixed $value): void
            {
                $_SESSION[$key] = $value;
            }
            
            public function forget(string $key): void
            {
                unset($_SESSION[$key]);
            }
            
            public function has(string $key): bool
            {
                return isset($_SESSION[$key]);
            }
            
            public function all(): array
            {
                return $_SESSION;
            }
            
            public function flush(): void
            {
                $_SESSION = [];
            }
        };
    }


    public function bind(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    public function resolve(string $key): mixed
    {
        if (!isset($this->bindings[$key])) {
            return null;
        }
        $resolver = $this->bindings[$key];
        return $resolver();
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public function loadRoutes(array $files = []): void
    {
        foreach ($files as $file) {
            $path = $this->resolveRouteFile($file);
            if (is_file($path)) {
                require $path;
            }
        }
    }

    protected function resolveRouteFile(string $file): string
    {
        if (strpos($file, DIRECTORY_SEPARATOR) === false) {
            return dirname(__DIR__) . "/routes/{$file}.php";
        }
        return $file;
    }

    public function registerMiddleware(array $middleware): void
    {
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
    }

    public function run(): void
    {
        try {
            $this->applyMiddleware(function () {
                $response = $this->router->dispatch($this->request);
                $this->emitResponse($response);
            });
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    protected function applyMiddleware(callable $final): void
    {
        $pipeline = array_reverse($this->resolveMiddlewareInstances($this->globalMiddleware));
        $next = $final;
        foreach ($pipeline as $middleware) {
            $prev = $next;
            $next = function () use ($middleware, $prev) {
                return $middleware->handle($this->request, $prev);
            };
        }
        $next();
    }

    public function getBasePath(): string
{
    return $this->basePath;
}


    protected function resolveMiddlewareInstances(array $middlewareList): array
    {
        $instances = [];
        foreach ($middlewareList as $item) {
            $name = $item;
            $params = [];
            if (is_string($item) && str_contains($item, ':')) {
                [$name, $paramString] = explode(':', $item, 2);
                $params = explode(',', $paramString);
            }
            $instance = $this->instantiateMiddleware($name);
            if ($instance) {
                $instances[] = new class($instance, $params) {
                    private $inner;
                    private array $params;
                    public function __construct($inner, array $params)
                    {
                        $this->inner = $inner;
                        $this->params = $params;
                    }
                    public function handle(Request $request, callable $next)
                    {
                        return $this->inner->handle($request, $next, ...$this->params);
                    }
                };
            }
        }
        return $instances;
    }

    protected function instantiateMiddleware(string $name): ?Middleware
    {
        if (class_exists($name)) {
            return new $name();
        }
        // Could map aliases here if implemented
        return null;
    }

    protected function emitResponse($response): void
    {
        if ($response instanceof Response) {
            $response->send();
            return;
        }
        // Fallback: raw content
        if (is_string($response)) {
            http_response_code(200);
            echo $response;
        } elseif (is_array($response)) {
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            http_response_code(200);
            echo (string) $response;
        }
    }

    protected function handleException(\Throwable $e): void
    {
        $this->logError($e);
        $debug = $this->config['debug'] ?? false;
        if ($debug) {
            http_response_code(500);
            echo "<pre>{$e}</pre>";
            return;
        }
        http_response_code(500);
        echo "Internal Server Error";
    }

    protected function logError(\Throwable $e): void
    {
        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $file = $logDir . '/app.log';
        $message = '[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        @file_put_contents($file, $message, FILE_APPEND);
    }

    public function route(string $name, array $params = []): ?string
    {
        if (method_exists($this->router, 'resolveName')) {
            return $this->router->resolveName($name, $params);
        }
        return null;
    }

    public function config(string $key, $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}

