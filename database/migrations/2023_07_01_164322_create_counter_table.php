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
        Schema::create('counter', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('partition');
            $table->string('interval');
            $table->bigInteger('time')->unsigned();
            $table->string('reference');
            $table->bigInteger('value')->unsigned();
            $table->boolean('is_rolled_up')->default(false);
            $table->boolean('is_executed')->default(false);

            // Indexes
            $table->index(['partition', 'interval', 'time', 'value', 'id']);
            $table->unique(['partition', 'interval', 'reference', 'time']);
            $table->index(['interval', 'time']);
            $table->index(['is_rolled_up', 'interval', 'time']);
            $table->index(['partition', 'is_rolled_up', 'interval', 'time']);
            $table->index(['partition', 'is_executed', 'interval', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter');
    }
};
