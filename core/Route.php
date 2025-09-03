<?php

namespace Core;

class Route
{
    protected array $methods;
    protected string $uri;
    protected mixed $action;
    protected ?string $name = null;
    protected array $middleware = [];

    public function __construct(array $methods, string $uri, mixed $action)
    {
        $this->methods = $methods;
        $this->uri = $uri;
        $this->action = $action;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function prefixName(string $prefix): self
    {
        if (!$this->name) {
            return $this;
        }
        $this->name = rtrim($prefix, '.') . '.' . $this->name;
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        if (is_string($middleware)) {
            $this->middleware[] = $middleware;
        } else {
            $this->middleware = array_merge($this->middleware, $middleware);
        }
        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): mixed
    {
        return $this->action;
    }

    public function buildUri(array $params = []): string
    {
        $uri = $this->uri;
        
        // Replace parameters in the URI
        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
            $uri = str_replace('{' . $key . '?}', $value, $uri);
        }
        
        // Remove optional parameters that weren't provided
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        
        // Clean up double slashes
        $uri = preg_replace('#/+#', '/', $uri);
        
        return $uri;
    }
}
