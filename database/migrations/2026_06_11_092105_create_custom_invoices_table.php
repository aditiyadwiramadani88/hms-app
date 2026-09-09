<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('guest_id')->constrained();
            $table->foreignId('booking_id')->nullable()->constrained('bookings');
            $table->string('source', 50);
            $table->string('room_name', 100);
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('nights');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->decimal('sell_price', 12, 2);
            $table->decimal('agent_commission', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'status']);
            $table->index(['hotel_id', 'created_at']);
            $table->index(['hotel_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_invoices');
    }
};
