<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $config = App::getInstance()->config('database');

        $dsn = $config['dsn'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $options = $config['options'] ?? [];

        // Set default options if not set
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => $config['persistent'] ?? false,
        ];

        $options = $options + $defaultOptions;

        try {
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function execute(string $sql, array $bindings = []): bool
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($bindings);
    }

    public function fetchAll(string $sql, array $bindings = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    public function fetchOne(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($bindings);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
