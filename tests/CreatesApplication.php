<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // During tests, avoid connecting to external Railway DB: point the 'railway'
        // connection to an in-memory sqlite database so migrations run isolated.
        if (env('APP_ENV') === 'testing') {
            $app['config']->set('database.connections.railway', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
        }

        return $app;
    }
}
