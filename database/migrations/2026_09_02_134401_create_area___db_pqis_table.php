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
        Schema::create('area___db_pqi', function (Blueprint $table) {
            $table->id();
            $table->integer('id_device');
            $table->integer('id_location');
            $table->integer('id_area');
            $table->integer('id_bagian');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area___db_pqi');
    }
};
