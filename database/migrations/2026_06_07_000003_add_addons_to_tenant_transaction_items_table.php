<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_transaction_items', function (Blueprint $table) {
            $table->text('addons_json')->nullable()->after('subtotal');
            $table->decimal('addons_total', 12, 2)->default(0)->after('addons_json');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_transaction_items', function (Blueprint $table) {
            $table->dropColumn(['addons_json', 'addons_total']);
        });
    }
};
