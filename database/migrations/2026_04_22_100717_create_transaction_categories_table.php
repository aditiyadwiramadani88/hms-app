<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['hotel_id', 'name', 'type']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
        Schema::dropIfExists('transaction_categories');
    }
};
