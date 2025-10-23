<?php
$databaseUrl = getenv('DATABASE_URL') ?: $argv[1] ?? null;
if (!$databaseUrl) {
    echo "Provide DATABASE_URL env or as first arg\n";
    exit(1);
}
$parts = parse_url($databaseUrl);
$host = $parts['host'] ?? null;
$port = $parts['port'] ?? 5432;
$user = $parts['user'] ?? null;
$pass = $parts['pass'] ?? null;
$path = $parts['path'] ?? null;
$dbname = $path ? ltrim($path, '/') : null;
parse_str($parts['query'] ?? '', $query);
$sslmode = $query['sslmode'] ?? null;
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
if ($sslmode) $dsn .= ";sslmode=$sslmode";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    echo "Connected OK\n";
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found:\n";
    foreach ($tables as $t) echo " - $t\n";
    $toDrop = ['users','comptes','transactions'];
    foreach ($toDrop as $t) {
        if (in_array($t, $tables)) {
            echo "Dropping $t...\n";
            $pdo->exec("DROP TABLE IF EXISTS \"$t\" CASCADE");
            echo "$t dropped\n";
        }
    }
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
}
