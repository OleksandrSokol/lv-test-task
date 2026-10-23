<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->string('template_key');
            $table->string('receiver');
            $table->string('subject');
            $table->string('sender')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('body');
            $table->string('status')->index();
            $table->text('error_message')->nullable();
            $table->timestampsTz();

            // A letter of a kind goes to an order once.
            $table->unique(['order_id', 'template_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_letters');
    }
};
