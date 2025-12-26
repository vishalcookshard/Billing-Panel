<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->nullable();
            $table->string('gateway_id')->nullable()->index();
            $table->string('idempotency_key')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('USD');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['invoice_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};