<?php
function get_pdo(): PDO {
    $url = getenv('DATABASE_PUBLIC_URL') ?: getenv('DATABASE_URL');

    if ($url) {
        $parts = parse_url($url);
        $host   = $parts['host'];
        $port   = $parts['port'] ?? 5432;
        $dbname = ltrim($parts['path'], '/');
        $user   = $parts['user'];
        $pass   = $parts['pass'];
    } else {
        $host   = getenv('PGHOST')     ?: 'localhost';
        $port   = getenv('PGPORT')     ?: '5432';
        $dbname = getenv('PGDATABASE') ?: 'railway';
        $user   = getenv('PGUSER')     ?: 'postgres';
        $pass   = getenv('PGPASSWORD') ?: '';
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

try {
    $pdo = get_pdo();
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}
