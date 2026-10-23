<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->string('order_external_id')->nullable()->index();
            $table->jsonb('payload');
            // The request body byte for byte, as signed; jsonb would reformat it.
            $table->text('raw_body');
            $table->timestampTz('occurred_at', 6);
            $table->string('status');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
