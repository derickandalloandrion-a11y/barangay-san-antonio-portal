<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) return self::$pdo;
        $file = dirname(__DIR__, 2) . '/config/database.php';
        if (!is_file($file)) throw new RuntimeException('Database configuration is missing. Copy config/database.example.php to config/database.php.');
        $c = require $file;
        if (!is_array($c)) throw new RuntimeException('Database configuration must return an array.');
        foreach (['host','port','database','username','password'] as $key) {
            if (!array_key_exists($key, $c) || !is_scalar($c[$key])) throw new RuntimeException('Database configuration is incomplete.');
        }
        $host = trim((string)$c['host']);
        $database = trim((string)$c['database']);
        $username = (string)$c['username'];
        $password = (string)$c['password'];
        $port = filter_var($c['port'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>65535]]);
        $charset = (string)($c['charset'] ?? 'utf8mb4');
        if ($host === '' || !preg_match('/^[A-Za-z0-9_.:-]{1,255}$/', $host)
            || $database === '' || !preg_match('/^[A-Za-z0-9_]{1,64}$/', $database)
            || $username === '' || $port === false || !in_array($charset, ['utf8mb4','utf8'], true)) {
            throw new RuntimeException('Database configuration contains invalid connection settings.');
        }
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
        self::$pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => false,
        ]);
        return self::$pdo;
    }
}
