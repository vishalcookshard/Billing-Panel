<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS invoices_prevent_paid_delete;
SQL
            );

            DB::unprepared(<<<'SQL'
CREATE TRIGGER invoices_prevent_paid_delete
BEFORE DELETE ON invoices FOR EACH ROW
BEGIN
    IF OLD.status = 'paid' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot delete a paid invoice';
    END IF;
END;
SQL
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::unprepared("DROP TRIGGER IF EXISTS invoices_prevent_paid_delete;");
        }
    }
};
