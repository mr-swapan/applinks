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
        if(!Schema::hasTable('applinks_data')){
            Schema::create('applinks_data', function (Blueprint $table) {
                $table->id();
    
                // Device info
                $table->string('device_type'); // android / ios
                $table->string('resolution')->nullable(); // "width x height"
                $table->string('ip_address')->nullable();
    
                // Stored query params
                $table->json('query_string')->nullable();
    
                // Timestamp (same as CI)
                $table->timestamp('created_at')->nullable();
    
                // Optional (recommended for Laravel consistency)
                $table->timestamp('updated_at')->nullable();
    
                // Indexes for faster lookup (important for your fetch logic)
                $table->index(['device_type']);
                $table->index(['ip_address']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applinks_data');
    }
};