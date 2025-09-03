<?php

namespace Core;

use Core\Database;
use Core\QueryBuilder;
use Exception;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    public bool $timestamps = true;

    protected array $attributes = [];
    protected array $original = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
        $this->resolveTableName();
    }

    protected function resolveTableName(): void
    {
        if (!isset($this->table)) {
            $class = (new \ReflectionClass($this))->getShortName();
            $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
            $this->table = $snake . 's';
        }
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        return new QueryBuilder($instance->table, $instance);
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        return static::query()->where($instance->primaryKey, $id)->first();
    }

    public static function findOrFail(mixed $id): static
    {
        $record = static::find($id);
        if (!$record) {
            throw new Exception(static::class . " not found.");
        }
        return $record;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        if (func_num_args() === 2) {
            return static::query()->where($column, $operator);
        }
        return static::query()->where($column, $operator, $value);
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');

        if ($this->timestamps) {
            $this->attributes['updated_at'] = $now;
        }

        if (!isset($this->attributes[$this->primaryKey])) {
            if ($this->timestamps) {
                $this->attributes['created_at'] = $now;
            }
            $id = static::query()->insert($this->attributes);
            if ($id) {
                $this->attributes[$this->primaryKey] = $id;
                $this->original = $this->attributes;
                return true;
            }
            return false;
        }

        $changed = $this->getDirtyAttributes();
        if (empty($changed)) {
            return true;
        }

        return static::query()
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->update($changed);
    }

    public static function create(array $attributes): static
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        return static::query()
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();
    }

    public static function newInstance(array $attributes = []): static
    {
        return new static($attributes);
    }

    public static function newFromBuilder(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->original = $attributes;
        return $model;
    }

    protected function getDirtyAttributes(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }
}
