<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Events\ClientCreated;

$user = User::where('email','client@example.net')->first();
if (!$user) { echo "NO_USER\n"; exit(0); }

$pwd = 'TestPass123';
$code = '654321';
event(new ClientCreated($user, $pwd, $code));

echo "EVENT DISPATCHED\n";
