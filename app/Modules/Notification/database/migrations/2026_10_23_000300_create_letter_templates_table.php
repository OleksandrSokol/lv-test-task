<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('sender')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_templates');
    }
};
