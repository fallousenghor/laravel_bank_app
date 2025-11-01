<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$comp = App\Models\Compte::first();
if ($comp) {
    echo $comp->id . PHP_EOL;
} else {
    echo "NO_COMPTE\n";
}
