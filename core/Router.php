<?php
namespace Core;

use Closure;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected array $currentGroup = [];
    protected static ?Router $instance = null;
    protected ?Dispatcher $dispatcher = null;

    public function __construct()
    {
        // No dependencies on App or Request
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $uri, $action): Route
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post(string $uri, $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function match(array $methods, string $uri, $action): Route
    {
        return $this->addRoute($methods, $uri, $action);
    }

    public function any(string $uri, $action): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $action);
    }

    public function group(array $attributes, Closure $callback): void
    {
    $this->currentGroup[] = $attributes;
    $callback($this);
    array_pop($this->currentGroup);
    // Clear dispatcher so it gets rebuilt with new routes
    $this->dispatcher = null;
    }

    protected function addRoute(array $methods, string $uri, $action): Route
    {
        $uri = $this->applyGroupPrefix($uri);
        $allGroups = $this->getAllGroups();
        
        // Controller grouping: check all groups for controller setting
        foreach (array_reverse($allGroups) as $group) {
            if (isset($group['controller']) && is_string($action) && !str_contains($action, '@')) {
                $action = $group['controller'] . '@' . $action;
                break;
            }
        }
        
        $route = new Route($methods, $uri, $action);

        // Apply middleware from all groups (parent to child order)
        foreach ($allGroups as $group) {
            if (isset($group['middleware'])) {
                $route->middleware($group['middleware']);
            }
        }
        
        // Apply name prefixes from all groups
        foreach ($allGroups as $group) {
            if (isset($group['name'])) {
                $route->prefixName($group['name']);
            }
        }

        // Handle wildcard routes
        if (str_ends_with($uri, '/*')) {
            $baseUri = rtrim($uri, '/*');
            // Add route for base path (e.g., /auth)
            foreach ($methods as $method) {
                $this->routes[] = [
                    'method' => $method,
                    'uri' => $baseUri,
                    'route' => $route,
                    'is_wildcard_base' => true
                ];
            }
            // Add route for wildcard pattern (e.g., /auth/{wildcard:.+})
            $wildcardUri = $baseUri . '/{wildcard:.+}';
            foreach ($methods as $method) {
                $this->routes[] = [
                    'method' => $method,
                    'uri' => $wildcardUri,
                    'route' => $route,
                    'is_wildcard' => true
                ];
            }
        } else {
            // Store regular routes for FastRoute
            foreach ($methods as $method) {
                $this->routes[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'route' => $route
                ];
            }
        }

        // Register named route if it has a name
        if ($route->getName()) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        // Clear dispatcher so it gets rebuilt with new routes
        $this->dispatcher = null;

        return $route;
    }

    protected function applyGroupPrefix(string $uri): string
    {
        $prefix = '';
        foreach ($this->currentGroup as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return '/' . trim($prefix . '/' . trim($uri, '/'), '/');
    }

    protected function getCurrentGroup(): ?array
    {
        return end($this->currentGroup) ?: null;
    }

    protected function getAllGroups(): array
    {
        return $this->currentGroup;
    }

    protected function getDispatcher(): Dispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = \FastRoute\simpleDispatcher(function(RouteCollector $r) {
                foreach ($this->routes as $route) {
                    $r->addRoute($route['method'], $route['uri'], $route['route']);
                }
            });
        }
        
        return $this->dispatcher;
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $uri = $request->uri();

        $dispatcher = $this->getDispatcher();
        $routeInfo = $dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // Try to handle trailing slash
                if (str_ends_with($uri, '/') && $uri !== '/') {
                    $uriWithoutSlash = rtrim($uri, '/');
                    $routeInfo = $dispatcher->dispatch($method, $uriWithoutSlash);
                    if ($routeInfo[0] === Dispatcher::FOUND) {
                        $route = $routeInfo[1];
                        $params = $routeInfo[2];
                        $request->setRouteParams($params);
                        return $this->runRoute($route, array_values($params), $request);
                    }
                }
                return $this->handleNotFound();
                
            case Dispatcher::METHOD_NOT_ALLOWED:
                return $this->handleMethodNotAllowed($routeInfo[1]);
                
            case Dispatcher::FOUND:
                $route = $routeInfo[1]; // This is our Route object
                $params = $routeInfo[2]; // URL parameters
                
                // Convert FastRoute params to our format
                $request->setRouteParams($params);
                return $this->runRoute($route, array_values($params), $request);
                
            default:
                return $this->handleNotFound();
        }
    }

    protected function runRoute(Route $route, array $params, Request $request): mixed
    {
        $middlewareStack = $route->getMiddleware();

        $core = function () use ($route, $params, $request) {
            $action = $route->getAction();

            if ($action instanceof Closure) {
                // For closures, prepend request as first parameter
                return call_user_func_array($action, array_merge([$request], $params));
            }

            if (is_string($action)) {
                [$controller, $method] = explode('@', $action);
                $controller = "App\\Controllers\\$controller";

                if (!class_exists($controller) || !method_exists($controller, $method)) {
                    throw new \Exception("Controller or method not found: $controller@$method");
                }

                $instance = new $controller();
                
                // For wildcard routes, add the wildcard parameter to the request
                if (isset($params['wildcard'])) {
                    $request->setWildcardPath($params['wildcard']);
                    // Remove wildcard from params to avoid duplicate parameters
                    unset($params['wildcard']);
                }
                
                // For controllers, prepend request as first parameter
                return call_user_func_array([$instance, $method], array_merge([$request], $params));
            }

            throw new \Exception("Invalid route action");
        };

        if (empty($middlewareStack)) {
            return $core();
        }

        $stack = array_reverse($middlewareStack);
        $next = $core;

        foreach ($stack as $middleware) {
            $prev = $next;
            $next = function () use ($middleware, $prev, $request) {
                if (str_contains($middleware, ':')) {
                    [$name, $paramStr] = explode(':', $middleware, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $name = $middleware;
                    $params = [];
                }

                if (!class_exists($name)) {
                    throw new \Exception("Middleware not found: $name");
                }

                $instance = new $name();
                return $instance->handle($request, $prev, ...$params);
            };
        }

        return $next();
    }

    protected function handleNotFound(): mixed
    {
        http_response_code(404);
        return view('notfound');
    }

    protected function handleMethodNotAllowed(array $allowedMethods): mixed
    {
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowedMethods));
        return "405 Method Not Allowed";
    }

    public function resolveName(string $name, array $params = []): ?string
    {
        if (!isset($this->namedRoutes[$name])) {
            return null;
        }

        $route = $this->namedRoutes[$name];
        return $route->buildUri($params);
    }

    public function generateUrl(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route named '{$name}' not found.");
        }

        $route = $this->namedRoutes[$name];
        return $route->buildUri($params);
    }
}
