<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$params = [
    'grant_type' => 'password',
    'client_id' => 6,
    'client_secret' => 'rkzced4wQV7ukvsaZFxVkR89lNLIVVgYGQGxapcU',
    'username' => 'admin@example.com',
    'password' => 'password123',
    'scope' => '',
];
$req = Illuminate\Http\Request::create('/oauth/token', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
$req->request->add($params);
file_put_contents('php://stdout', "Request content:" . $req->getContent() . "\n");
file_put_contents('php://stdout', "Parsed request all:" . json_encode($req->all()) . "\n");
file_put_contents('php://stdout', "Request->request:" . json_encode($req->request->all()) . "\n");
file_put_contents('php://stdout', "Request->query:" . json_encode($req->query->all()) . "\n");
file_put_contents('php://stdout', "php://input (getContent):" . $req->getContent() . "\n");
$resp = $app->handle($req);
file_put_contents('php://stdout', "Response status:" . $resp->getStatusCode() . "\n");
file_put_contents('php://stdout', "Response body:" . $resp->getContent() . "\n");
