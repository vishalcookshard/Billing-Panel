<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add audit/safety columns
            $table->dateTime('paid_at')->nullable()->after('amount');
            $table->string('idempotency_key')->nullable()->after('paid_at');
            $table->string('currency', 8)->default('USD')->after('idempotency_key');
            $table->dateTime('provisioned_at')->nullable()->after('automation_status');
            $table->softDeletes();

            // Indexes
            $table->index(['status']);
            $table->index(['user_id']);
            $table->unique(['idempotency_key']);
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'idempotency_key', 'currency', 'provisioned_at', 'deleted_at']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
        });
    }
};
