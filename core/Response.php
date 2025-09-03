<?php

namespace Core;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';
    protected bool $sent = false;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function setStatus(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$this->normalizeHeader($name)] = $value;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }

        echo $this->content;
        $this->sent = true;
    }

    public static function json(array $data, int $status = 200): self
    {
        return (new self())
            ->setStatus($status)
            ->setHeader('Content-Type', 'application/json')
            ->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function view(string $view, array $data = [], int $status = 200): self
    {
        $content = self::renderView($view, $data);
        return (new self())
            ->setStatus($status)
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setContent($content);
    }

    protected static function renderView(string $view, array $data = []): string
    {
        $viewPath = dirname(__DIR__) . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            return "View not found: {$view}";
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return (new self())
            ->setStatus($status)
            ->setHeader('Location', $url);
    }

    public static function sendRaw(string $content, int $status = 200, array $headers = []): self
    {
        $response = (new self())->setStatus($status)->setContent($content);
        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }
        return $response;
    }

    public static function noContent(int $status = 204): self
    {
        return (new self())->setStatus($status);
    }

    protected function normalizeHeader(string $name): string
    {
        $name = str_replace('_', '-', $name);
        $name = strtolower($name);
        $parts = explode('-', $name);
        $parts = array_map('ucfirst', $parts);
        return implode('-', $parts);
    }
}
