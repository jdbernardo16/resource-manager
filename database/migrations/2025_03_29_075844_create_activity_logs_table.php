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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('log_id'); // Use 'log_id' as the primary key name
            $table->timestamp('timestamp')->useCurrent(); // Default to current timestamp

            // Foreign key for users (nullable if action can be system-generated)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained(table: 'users', column: 'id') // Assuming default 'id' for users table
                  ->onUpdate('cascade')
                  ->onDelete('set null'); // Set user_id to null if user is deleted

            $table->string('action'); // e.g., 'PROJECT_CREATED', 'RESOURCE_ASSIGNED'
            $table->text('details')->nullable(); // Store JSON or relevant details
            // No need for $table->timestamps() here unless you want separate created_at/updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
