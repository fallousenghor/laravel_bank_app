<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create an in-memory user object (no DB required)
$user = new App\Models\User();
$user->prenom = 'Test';
$user->nom = 'User';
$user->email = 'devnull@example.com';
$user->telephone = '+221700000000';

// Dispatch event
// Instantiate listener directly to avoid queue serialization (and DB access)
$listener = new App\Listeners\SendClientNotification();
$listener->handle(new App\Events\ClientCreated($user, 'TempPass123', '654321'));

echo "Listener executed (sync)\n";
