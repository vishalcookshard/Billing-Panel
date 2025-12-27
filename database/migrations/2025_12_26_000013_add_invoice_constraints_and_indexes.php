<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $sm = $connection->getDoctrineSchemaManager();
        $indexes = [];
        foreach ($sm->listTableIndexes('invoices') as $idx) {
            $indexes[$idx->getName()] = true;
        }
        if (!isset($indexes['invoices_status_index'])) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index('status');
            });
        }
        if (!isset($indexes['invoices_service_id_index'])) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index('service_id');
            });
        }
        if (!isset($indexes['invoices_provisioned_at_index'])) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index('provisioned_at');
            });
        }

        // Add DB-level trigger to prevent modifying immutable fields after paid
        // This uses MySQL/MariaDB SIGNAL to throw an error when attempting forbidden changes
        // Only run when using MySQL-compatible driver
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS invoices_prevent_paid_update;
SQL
            );

            DB::unprepared(<<<'SQL'
CREATE TRIGGER invoices_prevent_paid_update
BEFORE UPDATE ON invoices FOR EACH ROW
BEGIN
    IF OLD.status = 'paid' THEN
        IF NEW.status <> 'paid' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot change status of a paid invoice';
        END IF;
        IF NEW.amount <> OLD.amount OR NEW.user_id <> OLD.user_id OR NEW.currency <> OLD.currency OR NEW.service_id <> OLD.service_id THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot modify immutable invoice fields after payment';
        END IF;
    END IF;
END;
SQL
            );
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['service_id']);
            $table->dropIndex(['provisioned_at']);
        });

        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::unprepared("DROP TRIGGER IF EXISTS invoices_prevent_paid_update;");
        }
    }
};
