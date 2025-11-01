<?php
require __DIR__ . '/vendor/autoload.php';
// Boot the framework
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$params = [
  'grant_type' => 'password',
  'client_id' => 1,
  'client_secret' => 'K7DQHfYsOuO4fM5NyApuWZiN6f7rNHic87fJ06gg',
  'username' => 'admin@example.com',
  'password' => 'password123',
  'scope' => ''
];

$req = Illuminate\Http\Request::create('/oauth/token', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
$req->request->add($params);
$resp = app()->handle($req);
echo $resp->getStatusCode() . "\n";
echo $resp->getContent() . "\n";
