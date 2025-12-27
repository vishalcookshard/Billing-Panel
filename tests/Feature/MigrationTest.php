<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    public function test_migrations_run_and_critical_tables_exist()
    {
        // Run migrations fresh to ensure migrations are valid
        $this->artisan('migrate:fresh --seed')->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasTable('webhook_events'));
        $this->assertTrue(Schema::hasTable('audits'));
    }
}
