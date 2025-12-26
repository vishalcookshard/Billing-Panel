<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 4);
            $table->string('currency', 8)->default('USD');
            $table->string('reason')->nullable();
            $table->string('status')->default('issued'); // issued|applied|voided
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_notes');
    }
};
