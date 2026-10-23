<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('number');
            $table->string('status')->index();
            $table->char('currency', 3);
            $table->string('customer_email');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->decimal('delivery_price', 12, 2);
            $table->decimal('discount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('shipment_carrier')->nullable();
            $table->string('shipment_tracking_number')->nullable();
            $table->text('shipment_tracking_url')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestampTz('external_created_at');
            $table->timestampTz('external_updated_at');
            $table->timestampsTz();

            $table->index(['external_created_at', 'id']);
            $table->rawIndex('lower(customer_email)', 'orders_customer_email_lower_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
