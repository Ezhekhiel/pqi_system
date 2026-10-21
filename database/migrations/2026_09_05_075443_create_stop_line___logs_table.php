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
        Schema::create('stop_line___logs', function (Blueprint $table) {
            $table->id();
            $table->string('id_device');
            $table->string('DFT_NM');
            $table->string('alert_option');
            $table->string('status')->default('NOTHING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stop_line___logs');
    }
};
