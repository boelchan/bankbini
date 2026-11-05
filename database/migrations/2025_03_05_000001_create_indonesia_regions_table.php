<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndonesiaRegionsTable extends Migration
{
    public function up()
    {
        Schema::create('indonesia_regions', function (Blueprint $table) {
            $table->bigInteger('code')->primary();
            $table->integer('parent')->nullable()->default(0);
            $table->string('name', 100);
            $table->string('type', 10);
            $table->string('postal_code', 6)->nullable();
            $table->decimal('latitude', 10, 8)->nullable()->default(0);
            $table->decimal('longitude', 11, 8)->nullable()->default(0);
            $table->string('status')->nullable();

            // Basic indexes
            $table->index('name');
            $table->index('type');
            $table->index('postal_code');
            $table->index('parent');
        });
    }

    public function down()
    {
        Schema::dropIfExists('indonesia_regions');
    }
}
