<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Make invoice optional so audits can be used for admin actions too
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('invoice_id');
            $table->string('action')->nullable()->after('event');
            $table->text('notes')->nullable()->after('meta');
            $table->nullableMorphs('actor');
        });
    }

    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'action', 'notes']);
            $table->dropMorphs('actor');
        });
    }
};
