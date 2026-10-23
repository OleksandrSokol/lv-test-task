<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->nullableMorphs('subject');
            $table->jsonb('data')->nullable();
            $table->string('source');
            $table->timestampTz('created_at');

            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
