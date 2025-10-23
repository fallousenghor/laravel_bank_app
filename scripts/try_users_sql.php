<?php
$databaseUrl = getenv('DATABASE_URL') ?: $argv[1] ?? null;
if (!$databaseUrl) {
    echo "Provide DATABASE_URL env or as first arg\n";
    exit(1);
}
// parse DATABASE_URL
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
    $create = 'CREATE TABLE IF NOT EXISTS "users" ("id" bigserial not null primary key, "name" varchar(255) not null, "email" varchar(255) not null, "email_verified_at" timestamp without time zone null, "password" varchar(255) not null, "remember_token" varchar(100) null, "created_at" timestamp without time zone null, "updated_at" timestamp without time zone null)';
    $alter = 'ALTER TABLE "users" ADD CONSTRAINT "users_email_unique" UNIQUE ("email")';
    echo "Running CREATE...\n";
    $pdo->exec($create);
    echo "CREATE done\n";
    echo "Running ALTER...\n";
    $pdo->exec($alter);
    echo "ALTER done\n";
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
}
