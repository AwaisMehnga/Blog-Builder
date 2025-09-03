<?php

namespace Core;

abstract class Controller
{
    protected Request $request;
    protected App $app;

    public function __construct()
    {
        $this->app = App::getInstance();
        $this->request = $this->app->getRequest();

        if (method_exists($this, 'boot')) {
            $this->boot();
        }
    }

    /**
     * Return a JSON response.
     */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Return a view response.
     */
    protected function view(string $view, array $data = [], int $status = 200): Response
    {
        return Response::view($view, $data, $status);
    }

    /**
     * Return a redirect response.
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Return an empty/no-content response.
     */
    protected function noContent(int $status = 204): Response
    {
        return Response::noContent($status);
    }

    /**
     * Access a single input value.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->request->input($key, $default);
    }

    /**
     * Get all input data (query + body).
     */
    protected function all(): array
    {
        return $this->request->all();
    }

    /**
     * Check if a given input key is present.
     */
    protected function has(string $key): bool
    {
        return $this->request->has($key);
    }

    /**
     * Optional boot method for child controllers.
     * Override this in your controller if needed.
     */
    protected function boot(): void
    {
        // This method is intentionally left empty for overriding
    }
}
