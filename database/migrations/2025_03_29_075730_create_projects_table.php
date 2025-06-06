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
        Schema::create('projects', function (Blueprint $table) {
            $table->id('project_id'); // Use 'project_id' as the primary key name
            $table->string('project_name');
            $table->text('project_description')->nullable();
            $table->date('start_date');
            $table->integer('time_estimate_hours');
            $table->date('deadline')->nullable(); // Add nullable deadline date
            $table->string('status')->default('active'); // Add status column with default
            $table->boolean('is_task')->default(false);
            $table->timestamps(); // Adds created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
