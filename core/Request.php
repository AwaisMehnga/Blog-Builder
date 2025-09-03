<?php

namespace Core;

class Request
{
    protected string $method;
    protected string $uri;
    protected array $query = [];
    protected array $body = [];
    protected ?array $json = null;
    protected array $headers = [];
    protected array $server = [];
    protected array $cookies = [];
    protected array $files = [];
    protected array $routeParams = [];
    protected string $rawBody;

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->method = $this->normalizeMethod($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->extractUri();
        $this->query = $_GET;
        $this->cookies = $_COOKIE;
        $this->headers = $this->extractHeaders();
        $this->rawBody = file_get_contents('php://input');
        $this->body = $this->parseBody();
        $this->files = $this->normalizeFiles($_FILES);
    }

    protected function normalizeMethod(string $method): string
    {
        return strtoupper($method);
    }

    protected function extractUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return '/' . ltrim($uri, '/');
    }

    protected function extractHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    protected function parseBody(): array
    {
        $contentType = $this->header('Content-Type') ?? '';
        if ($this->isJson() || str_contains(strtolower($contentType), 'application/json')) {
            $decoded = json_decode($this->rawBody, true);
            $this->json = is_array($decoded) ? $decoded : [];
            return $this->sanitize($this->json);
        }
        if ($this->method === 'PUT' || $this->method === 'PATCH' || $this->method === 'DELETE') {
            parse_str($this->rawBody, $parsed);
            return $this->sanitize(is_array($parsed) ? $parsed : []);
        }
        // Default form-encoded or multipart falls back to $_POST
        return $this->sanitize($_POST);
    }

    protected function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $info) {
            if (!is_array($info['name'])) {
                $normalized[$key] = new UploadedFile(
                    $info['tmp_name'] ?? null,
                    $info['name'] ?? null,
                    $info['size'] ?? null,
                    $info['error'] ?? null,
                    $info['type'] ?? null
                );
                continue;
            }
            // multiple
            $items = [];
            $count = count($info['name']);
            for ($i = 0; $i < $count; $i++) {
                $items[] = new UploadedFile(
                    $info['tmp_name'][$i] ?? null,
                    $info['name'][$i] ?? null,
                    $info['size'][$i] ?? null,
                    $info['error'][$i] ?? null,
                    $info['type'][$i] ?? null
                );
            }
            $normalized[$key] = $items;
        }
        return $normalized;
    }

    protected function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $k => $v) {
                $clean[$k] = $this->sanitize($v);
            }
            return $clean;
        }
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $verb): bool
    {
        return strtoupper($verb) === $this->method;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }

    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->header('Host') ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));
        $query = http_build_query($this->query);
        $uri = $this->uri;
        return $scheme . '://' . $host . $uri . ($query !== '' ? '?' . $query : '');
    }

    public function isSecure(): bool
    {
        if (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') {
            return true;
        }
        if (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        return false;
    }

    public function query(string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(string $key = null, $default = null): mixed
    {
        $data = array_merge($this->query, $this->body);
        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    public function raw(string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->rawBody;
        }
        // raw only considers JSON or raw body; no merge
        if ($this->isJson()) {
            return $this->json[$key] ?? $default;
        }
        parse_str($this->rawBody, $parsed);
        return $parsed[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        $data = $this->all();
        return array_intersect_key($data, array_flip($keys));
    }

    public function except(array $keys): array
    {
        $data = $this->all();
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    public function has(string $key): bool
    {
        $data = $this->all();
        return array_key_exists($key, $data);
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return !($value === null || $value === '' || (is_array($value) && empty($value)));
    }

    public function header(string $key, $default = null): mixed
    {
        $normalized = $this->normalizeHeaderName($key);
        return $this->headers[$normalized] ?? $default;
    }

    protected function normalizeHeaderName(string $name): string
    {
        $name = str_replace('-', ' ', $name);
        $name = ucwords(strtolower($name));
        return str_replace(' ', '-', $name);
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        if (!$auth) {
            return null;
        }
        if (preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type') ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function cookie(string $key, $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function server(string $key, $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function ip(): ?string
    {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $list = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($list[0]);
        }
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }

    protected ?string $wildcardPath = null;

    public function setWildcardPath(?string $path): void
    {
        $this->wildcardPath = $path;
    }

    public function getWildcardPath(): ?string
    {
        return $this->wildcardPath;
    }

    public function hasWildcard(): bool
    {
        return $this->wildcardPath !== null;
    }
}

/**
 * Lightweight uploaded file abstraction.
 */
class UploadedFile
{
    protected ?string $tmpPath;
    protected ?string $originalName;
    protected ?int $size;
    protected ?int $error;
    protected ?string $mimeType;

    public function __construct(?string $tmpPath, ?string $originalName, ?int $size, ?int $error, ?string $mimeType)
    {
        $this->tmpPath = $tmpPath;
        $this->originalName = $originalName;
        $this->size = $size;
        $this->error = $error;
        $this->mimeType = $mimeType;
    }

    public function getTempPath(): ?string
    {
        return $this->tmpPath;
    }

    public function getClientName(): ?string
    {
        return $this->originalName;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): ?int
    {
        return $this->error;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && $this->tmpPath !== null;
    }

    public function move(string $destination): bool
    {
        if (!$this->isValid() || $this->tmpPath === null) {
            return false;
        }
        return move_uploaded_file($this->tmpPath, $destination);
    }
}
