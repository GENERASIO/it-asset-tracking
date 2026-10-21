<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_references', function (Blueprint $table) {
            $table->id();
            // Nama model seperti yang dibaca agent (brand + model), mis. "Dell Inc. Vostro 3400".
            $table->string('match_name')->unique();
            $table->bigInteger('purchase_price');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Isi awal dari harga Dell yang sudah dikonfirmasi tim aset.
        $seed = [
            ['match_name' => 'Dell Inc. Vostro 3400', 'purchase_price' => 4000000],
            ['match_name' => 'Dell Inc. Vostro 3401', 'purchase_price' => 3399000],
            ['match_name' => 'Dell Inc. Latitude 3410', 'purchase_price' => 6999000],
            ['match_name' => 'Dell Inc. Vostro 15 3510', 'purchase_price' => 6660000],
            ['match_name' => 'Dell Inc. Latitude 3420', 'purchase_price' => 8214000],
        ];
        foreach ($seed as &$row) {
            $row['note'] = 'Data tim aset';
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }
        DB::table('price_references')->insert($seed);
    }

    public function down(): void
    {
        Schema::dropIfExists('price_references');
    }
};
