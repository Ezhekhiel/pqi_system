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
        Schema::create('area_iot___dbs', function (Blueprint $table) {
            $table->id();
            $table->string('id_device');
            $table->string('id_bagian');
            $table->string('id_area');
            $table->string('id_location');
            $table->string('id_proses')->nullable();
            $table->string('remark')->nullable();
            $table->string('coordinate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_iot___dbs');
    }
};
