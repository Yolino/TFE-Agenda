<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsTestSchema;

abstract class DatabaseTestCase extends TestCase
{
    use BuildsTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['mysql', 'bti'] as $connection) {
            config()->set("database.connections.{$connection}", [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ]);

            DB::purge($connection);
        }

        $this->buildTestSchema();
    }
}
