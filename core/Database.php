<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public function __construct()
    {
        if (self::$pdo === null) {
            $this->connect();
        }
    }

    private function connect()
    {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $database = $_ENV['DB_DATABASE'];
        $username = $_ENV['DB_USERNAME'];
        $password = $_ENV['DB_PASSWORD'];

        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
            self::$pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            new self();
        }
        return self::$pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

