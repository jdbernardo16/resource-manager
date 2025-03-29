<?php

namespace App\Observers;

use App\Models\ActivityLog; // Import ActivityLog
use App\Models\Project;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(), // Get current logged-in user ID, or null if not logged in (e.g., seeder)
            'action' => 'PROJECT_CREATED',
            'details' => json_encode([ // Store relevant details as JSON
                'project_id' => $project->project_id,
                'project_name' => $project->project_name,
                'is_task' => $project->is_task,
            ]),
            // 'timestamp' will be set automatically by the database default
        ]);
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'PROJECT_UPDATED',
            'details' => json_encode([
                'project_id' => $project->project_id,
                'project_name' => $project->project_name,
                'changes' => $project->getChanges(), // Log what specifically changed
            ]),
        ]);
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'PROJECT_DELETED',
            'details' => json_encode([
                'project_id' => $project->project_id,
                'project_name' => $project->project_name,
            ]),
        ]);
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        // Optional: Log restoration if using soft deletes
    }

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleted(Project $project): void
    {
        // Optional: Log force deletion if using soft deletes
    }
}
