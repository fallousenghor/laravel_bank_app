<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$u = App\Models\User::where('role', 'admin')->first();
if ($u) echo $u->id . PHP_EOL; else echo "NO_ADMIN\n";
