<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Rupiah tidak memakai sen, jadi harga beli disimpan sebagai bilangan bulat Rupiah penuh.
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_price')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)->nullable()->change();
        });
    }
};
