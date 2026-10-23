<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestampTz('changed_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['order_id', 'status', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
