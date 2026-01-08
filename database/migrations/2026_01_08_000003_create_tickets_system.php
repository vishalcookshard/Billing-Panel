<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('ticket_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained('ticket_departments')->onDelete('cascade');
            $table->string('subject');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'closed', 'pending', 'resolved'])->default('open');
            $table->timestamps();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reply_id')->constrained('ticket_replies')->onDelete('cascade');
            $table->string('filename');
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('department_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('ticket_departments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('department_staff');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_departments');
    }
};
