<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    $migrations = DB::select("select table_name from information_schema.tables where table_name IN ('migrations','users')");
    echo "Tables found:\n";
    foreach ($migrations as $row) {
        echo " - {$row->table_name}\n";
    }
} catch (Exception $e) {
    echo "Error: ". $e->getMessage() ."\n";
}
