<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('module');
            $table->string('external_id')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated'])->default('pending');
            $table->json('config')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamp('next_due_date')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
};
