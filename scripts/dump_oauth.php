<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$personal = DB::table('oauth_personal_access_clients')->get();
$clients = DB::table('oauth_clients')->get();

echo "oauth_personal_access_clients:\n";
print_r($personal->toArray());

echo "\noauth_clients:\n";
print_r($clients->toArray());
