<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('DB_CONNECTION', 'pgsql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'archive_mysql' => [
            'driver' => 'mysql',
            'url' => env('ARCHIVE_DATABASE_URL'),
            'host' => env('ARCHIVE_DB_HOST', '127.0.0.1'),
            'port' => env('ARCHIVE_DB_PORT', '3306'),
            'database' => env('ARCHIVE_DB_DATABASE', 'bank_archive'),
            'username' => env('ARCHIVE_DB_USERNAME', 'forge'),
            'password' => env('ARCHIVE_DB_PASSWORD', ''),
            'unix_socket' => env('ARCHIVE_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('ARCHIVE_MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL', 'postgresql://neondb_owner:npg_5biZ2QOaCwxd@ep-noisy-mud-a4aa8ujp-pooler.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require'),
            'host' => env('DB_HOST', 'ep-noisy-mud-a4aa8ujp-pooler.us-east-1.aws.neon.tech'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'neondb'),
            'username' => env('DB_USERNAME', 'neondb_owner'),
            'password' => env('DB_PASSWORD', 'npg_5biZ2QOaCwxd'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'require',
            'timezone' => 'UTC',
        ],

        'archive' => [
            'driver' => 'pgsql',
            'url' => env('ARCHIVE_DATABASE_URL', 'postgresql://neondb_owner:npg_5biZ2QOaCwxd@ep-noisy-mud-a4aa8ujp-pooler.us-east-1.aws.neon.tech/archive_neondb?sslmode=require&channel_binding=require'),
            'host' => env('ARCHIVE_DB_HOST', 'ep-noisy-mud-a4aa8ujp-pooler.us-east-1.aws.neon.tech'),
            'port' => env('ARCHIVE_DB_PORT', '5432'),
            'database' => env('ARCHIVE_DB_DATABASE', 'archive_neondb'),
            'username' => env('ARCHIVE_DB_USERNAME', 'neondb_owner'),
            'password' => env('ARCHIVE_DB_PASSWORD', 'npg_5biZ2QOaCwxd'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'require',
            'channel_binding' => 'require',
            'timezone' => 'UTC',
        ],

        /*
        |--------------------------------------------------------------------------
        | Railway PostgreSQL (secondaire)
        |--------------------------------------------------------------------------
        | Connexion additionnelle vers la base de données hébergée sur Railway.
        | Utilisez les variables d'environnement RAILWAY_DB_* ou RAILWAY_DATABASE_URL
        | pour fournir les informations de connexion.
        */
        'railway' => [
            'driver' => 'pgsql',
            /*
             * Accept either a dedicated RAILWAY_DATABASE_URL or the generic
             * DATABASE_URL that Railway exposes. Also allow PGHOST/PGUSER/... as
             * provided by Railway for individual parts.
             */
            'url' => env('RAILWAY_DATABASE_URL', env('DATABASE_URL')),
            'host' => env('RAILWAY_DB_HOST', env('PGHOST', '127.0.0.1')),
            'port' => env('RAILWAY_DB_PORT', env('PGPORT', '5432')),
            'database' => env('RAILWAY_DB_DATABASE', env('PGDATABASE', 'railway_db')),
            'username' => env('RAILWAY_DB_USERNAME', env('PGUSER', 'railway_user')),
            'password' => env('RAILWAY_DB_PASSWORD', env('PGPASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => env('RAILWAY_DB_SCHEMA', env('PGSCHEMA', 'public')),
            'sslmode' => env('RAILWAY_DB_SSLMODE', env('PGSSLMODE', 'require')),
            'timezone' => 'UTC',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
