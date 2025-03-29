<?php

namespace App\Observers;

use App\Models\ActivityLog; // Import ActivityLog
use App\Models\ProjectAssignment;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class ProjectAssignmentObserver
{
    /**
     * Handle the ProjectAssignment "created" event.
     * Log when a resource is assigned to a project.
     */
    public function created(ProjectAssignment $assignment): void
    {
        // Eager load related models to get names for the log details
        $assignment->load(['project', 'resource']);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'ASSIGNMENT_CREATED',
            'details' => json_encode([
                'assignment_id' => $assignment->assignment_id,
                'project_id' => $assignment->project_id,
                'project_name' => $assignment->project?->project_name, // Use optional chaining
                'resource_id' => $assignment->resource_id,
                'resource_name' => $assignment->resource?->name, // Use optional chaining
                'start_date' => $assignment->assignment_start_date->toDateString(),
                'estimated_end_date' => $assignment->assignment_end_date?->toDateString(),
            ]),
        ]);
    }

    /**
     * Handle the ProjectAssignment "updated" event.
     * Specifically check if the assignment was marked as completed.
     */
    public function updated(ProjectAssignment $assignment): void
    {
        $assignment->load(['project', 'resource']); // Load relations for details

        // Check if 'assignment_is_active' was changed from true to false
        if ($assignment->wasChanged('assignment_is_active') && $assignment->getOriginal('assignment_is_active') === true && $assignment->assignment_is_active === false) {
            $action = 'ASSIGNMENT_COMPLETED';
            $details = [
                'assignment_id' => $assignment->assignment_id,
                'project_id' => $assignment->project_id,
                'project_name' => $assignment->project?->project_name,
                'resource_id' => $assignment->resource_id,
                'resource_name' => $assignment->resource?->name,
                'completion_date' => $assignment->assignment_end_date?->toDateString() ?? now()->toDateString(), // Use end date or today
            ];
        } else {
            // Log other updates if needed, or ignore them
            // For now, we only explicitly log completion via update.
            // If you want to log other changes (e.g., end date changes), add another log entry.
            // $action = 'ASSIGNMENT_UPDATED';
            // $details = [
            //     'assignment_id' => $assignment->assignment_id,
            //     'changes' => $assignment->getChanges(),
            // ];
            return; // Exit if it wasn't a completion event
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'details' => json_encode($details),
        ]);
    }

    /**
     * Handle the ProjectAssignment "deleted" event.
     * Log when an assignment is removed (e.g., project deleted).
     */
    public function deleted(ProjectAssignment $assignment): void
    {
        // Note: Accessing relations on a deleted model might be tricky.
        // It's better to log based on the state *before* deletion if possible,
        // or rely on the IDs stored in the assignment model itself.
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'ASSIGNMENT_DELETED',
            'details' => json_encode([
                'assignment_id' => $assignment->assignment_id,
                'project_id' => $assignment->project_id,
                'resource_id' => $assignment->resource_id,
                // Names might not be available here easily after deletion
            ]),
        ]);
    }

    /**
     * Handle the ProjectAssignment "restored" event.
     */
    public function restored(ProjectAssignment $projectAssignment): void
    {
        // Optional: Log restoration if using soft deletes
    }

    /**
     * Handle the ProjectAssignment "force deleted" event.
     */
    public function forceDeleted(ProjectAssignment $projectAssignment): void
    {
        // Optional: Log force deletion if using soft deletes
    }
}
