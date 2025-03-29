<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Import DB facade for raw SQL if needed later

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id('assignment_id'); // Use 'assignment_id' as the primary key name

            // Foreign key for projects
            $table->foreignId('project_id')
                  ->constrained(table: 'projects', column: 'project_id') // Explicitly reference table and column
                  ->onUpdate('cascade')
                  ->onDelete('cascade'); // Or restrict/set null depending on desired behavior

            // Foreign key for resources
            $table->foreignId('resource_id')
                  ->constrained(table: 'resources', column: 'resource_id') // Explicitly reference table and column
                  ->onUpdate('cascade')
                  ->onDelete('cascade'); // Or restrict/set null

            $table->date('assignment_start_date');
            $table->date('assignment_end_date')->nullable();
            $table->boolean('assignment_is_active')->default(true);

            $table->timestamps(); // Adds created_at and updated_at

            // Unique constraint to ensure a resource has only one active assignment.
            // Note: The original plan suggested a partial index `WHERE assignment_is_active = TRUE`.
            // This composite index achieves a similar goal for this specific case (only one row per resource_id where assignment_is_active is true).
            // For true partial index support, raw SQL might be needed depending on the database driver.
            // Example for PostgreSQL:
            // DB::statement('CREATE UNIQUE INDEX unique_active_resource ON project_assignments (resource_id) WHERE assignment_is_active = TRUE;');
            // We'll use the composite index for broader compatibility first.
            // Unique constraint removed - rely on application logic in ProjectController
            // to ensure only one active assignment per resource.

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};
