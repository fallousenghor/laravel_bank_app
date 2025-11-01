<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$u = User::orderBy('created_at', 'desc')->first();
if (!$u) {
    echo "NO_USER\n";
    exit(0);
}
echo json_encode([
    'id' => (string)$u->id,
    'email' => $u->email,
    'created_at' => (string)$u->created_at,
]);
