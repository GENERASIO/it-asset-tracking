<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Menandai harga beli yang diisi dari estimasi harga pasar (data harga asli hilang),
        // supaya tidak tertukar dengan harga beli sebenarnya.
        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('purchase_price_estimated')->default(false)->after('purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('purchase_price_estimated');
        });
    }
};
