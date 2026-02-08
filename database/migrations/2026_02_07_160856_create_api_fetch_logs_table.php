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
        Schema::create('api_fetch_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('status');
            $table->integer('articles_fetched')->default(0);
            $table->integer('articles_created')->default(0);
            $table->integer('articles_updated')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            //            $table->index(['source', 'fetched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_fetch_logs');
    }
};
