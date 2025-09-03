<?php

namespace Core;

use Core\Database;
use Exception;

class QueryBuilder
{
    protected string $table;
    protected ?Model $model = null;

    protected array $selects = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(string $table, ?Model $model = null)
    {
        $this->table = $table;
        $this->model = $model;
    }

    public function select(array|string $columns): static
    {
        if (is_string($columns)) {
            $columns = func_get_args();
        }
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value');
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = [
            'type' => 'or',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        $this->bindings[] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        $results = Database::getInstance()->fetchAll($sql, $this->bindings);
        if ($this->model) {
            return array_map(fn($row) => $this->model->newFromBuilder($row), $results);
        }
        return $results;
    }

    public function first(): mixed
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        $sql = $this->compileCountSql();
        $result = Database::getInstance()->fetchOne($sql, $this->bindings);
        return (int) ($result['count'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function insert(array $data): int|false
    {
        $db = Database::getInstance();

        if ($this->model && $this->model->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }

        $columns = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `{$this->table}` (" .
            implode(',', array_map(fn($col) => "`$col`", $columns)) .
            ") VALUES ($placeholders)";

        $success = $db->execute($sql, array_values($data));

        if (!$success) {
            return false;
        }

        return (int) $db->lastInsertId();
    }

    public function update(array $data): bool
    {
        $db = Database::getInstance();

        if ($this->model && $this->model->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $setParts = [];
        $setBindings = [];
        foreach ($data as $column => $value) {
            $setParts[] = "`$column` = ?";
            $setBindings[] = $value;
        }

        if (empty($this->wheres)) {
            throw new Exception('Update operation requires a WHERE clause to prevent mass updates.');
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $setParts) . $this->compileWhereSql();

        $bindings = array_merge($setBindings, $this->bindings);

        return $db->execute($sql, $bindings);
    }

    public function delete(): bool
    {
        $db = Database::getInstance();

        if (empty($this->wheres)) {
            throw new Exception('Delete operation requires a WHERE clause to prevent mass deletes.');
        }

        $sql = "DELETE FROM `{$this->table}`" . $this->compileWhereSql();

        return $db->execute($sql, $this->bindings);
    }

    protected function toSql(): string
    {
        $columns = implode(',', array_map(fn($col) => $col === '*' ? '*' : "`$col`", $this->selects));
        $sql = "SELECT $columns FROM `{$this->table}`";
        $sql .= $this->compileWhereSql();
        $sql .= $this->compileOrderBySql();

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    protected function compileCountSql(): string
    {
        $sql = "SELECT COUNT(*) AS count FROM `{$this->table}`";
        $sql .= $this->compileWhereSql();
        return $sql;
    }

    protected function compileWhereSql(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $clauses = [];
        foreach ($this->wheres as $index => $where) {
            if (isset($where['type']) && $where['type'] === 'or') {
                $prefix = $index === 0 ? 'WHERE' : 'OR';
            } else {
                $prefix = $index === 0 ? 'WHERE' : 'AND';
            }

            $clauses[] = "$prefix `{$where['column']}` {$where['operator']} ?";
        }

        return ' ' . implode(' ', $clauses);
    }

    protected function compileOrderBySql(): string
    {
        if (empty($this->orders)) {
            return '';
        }

        $parts = array_map(fn($order) => "`{$order['column']}` {$order['direction']}", $this->orders);

        return ' ORDER BY ' . implode(', ', $parts);
    }

    /**
     * Paginate the results
     * 
     * @param int $perPage Number of items per page
     * @param int $page Current page number (1-based)
     * @param string $pageName Query parameter name for page
     * @return array Paginated results with metadata
     */
    public function paginate(int $perPage = 15, int $page = null, string $pageName = 'page'): array
    {
        // Get current page from request if not provided
        if ($page === null) {
            $app = \Core\App::getInstance();
            $request = $app->getRequest();
            $page = max(1, (int) $request->query($pageName, 1));
        }

        // Ensure page is at least 1
        $page = max(1, $page);

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get total count before applying limit/offset
        $total = $this->count();

        // Apply limit and offset
        $this->limit($perPage)->offset($offset);

        // Get the results
        $items = $this->get();

        // Calculate pagination metadata
        $lastPage = (int) ceil($total / $perPage);
        $from = $total > 0 ? $offset + 1 : 0;
        $to = min($offset + $perPage, $total);

        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to,
            'has_more_pages' => $page < $lastPage,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $lastPage ? $page + 1 : null,
            'path' => $this->getCurrentPath(),
            'links' => $this->generatePaginationLinks($page, $lastPage, $pageName)
        ];
    }

    /**
     * Simple paginate - only shows next/previous links
     * 
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string $pageName Query parameter name for page
     * @return array Simplified pagination results
     */
    public function simplePaginate(int $perPage = 15, int $page = null, string $pageName = 'page'): array
    {
        // Get current page from request if not provided
        if ($page === null) {
            $app = \Core\App::getInstance();
            $request = $app->getRequest();
            $page = max(1, (int) $request->query($pageName, 1));
        }

        // Ensure page is at least 1
        $page = max(1, $page);

        // Calculate offset and fetch one extra item to check if there are more pages
        $offset = ($page - 1) * $perPage;
        $this->limit($perPage + 1)->offset($offset);

        // Get the results
        $items = $this->get();

        // Check if there are more pages
        $hasMorePages = count($items) > $perPage;

        // Remove the extra item if present
        if ($hasMorePages) {
            array_pop($items);
        }

        $from = count($items) > 0 ? $offset + 1 : 0;
        $to = $offset + count($items);

        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'from' => $from,
            'to' => $to,
            'has_more_pages' => $hasMorePages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $hasMorePages ? $page + 1 : null,
            'path' => $this->getCurrentPath()
        ];
    }

    /**
     * Get current request path
     */
    protected function getCurrentPath(): string
    {
        $app = \Core\App::getInstance();
        $request = $app->getRequest();
        return strtok($request->uri(), '?');
    }

    /**
     * Generate pagination links
     */
    protected function generatePaginationLinks(int $currentPage, int $lastPage, string $pageName): array
    {
        $links = [];
        $window = 3; // Number of pages to show on each side of current page

        // Previous link
        if ($currentPage > 1) {
            $links[] = [
                'url' => $this->buildPageUrl($currentPage - 1, $pageName),
                'label' => '&laquo; Previous',
                'active' => false
            ];
        }

        // First page
        if ($currentPage > $window + 1) {
            $links[] = [
                'url' => $this->buildPageUrl(1, $pageName),
                'label' => '1',
                'active' => false
            ];

            if ($currentPage > $window + 2) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false
                ];
            }
        }

        // Pages around current page
        $start = max(1, $currentPage - $window);
        $end = min($lastPage, $currentPage + $window);

        for ($i = $start; $i <= $end; $i++) {
            $links[] = [
                'url' => $this->buildPageUrl($i, $pageName),
                'label' => (string) $i,
                'active' => $i === $currentPage
            ];
        }

        // Last page
        if ($currentPage < $lastPage - $window) {
            if ($currentPage < $lastPage - $window - 1) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false
                ];
            }

            $links[] = [
                'url' => $this->buildPageUrl($lastPage, $pageName),
                'label' => (string) $lastPage,
                'active' => false
            ];
        }

        // Next link
        if ($currentPage < $lastPage) {
            $links[] = [
                'url' => $this->buildPageUrl($currentPage + 1, $pageName),
                'label' => 'Next &raquo;',
                'active' => false
            ];
        }

        return $links;
    }

    /**
     * Build page URL with query parameters
     */
    protected function buildPageUrl(int $page, string $pageName): string
    {
        $app = \Core\App::getInstance();
        $request = $app->getRequest();
        
        $queryParams = $request->query();
        $queryParams[$pageName] = $page;
        
        $path = $this->getCurrentPath();
        $queryString = http_build_query($queryParams);
        
        return $path . ($queryString ? '?' . $queryString : '');
    }
}
       