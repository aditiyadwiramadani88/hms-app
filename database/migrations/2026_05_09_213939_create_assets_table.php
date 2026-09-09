<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('hotel_id');
            $table->bigInteger('asset_category_id');
            $table->string('name', 255);
            $table->string('serial_number', 100)->nullable();
            $table->integer('purchase_year');
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->string('supplier', 255)->nullable();
            $table->string('location_type', 50);
            $table->bigInteger('room_id')->nullable();
            $table->string('area_name', 255)->nullable();
            $table->bigInteger('pic_user_id')->nullable();
            $table->string('pic_role', 100)->nullable();
            $table->string('status')->default('Baik');
            $table->text('condition_notes')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
            
            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
            $table->foreign('pic_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
