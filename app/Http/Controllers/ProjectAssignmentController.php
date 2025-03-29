<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon; // Import Carbon for date handling

class ProjectAssignmentController extends Controller
{
    /**
     * Mark the specified project assignment as complete.
     *
     * @param  \App\Models\ProjectAssignment  $assignment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function complete(ProjectAssignment $assignment): RedirectResponse
    {
        // Ensure the assignment is actually active before completing
        if ($assignment->assignment_is_active) {
            $assignment->assignment_is_active = false;
            $assignment->assignment_end_date = Carbon::today(); // Set end date to today
            $assignment->save();

            // Optional: Add Activity Log entry here later

            return Redirect::back()->with('success', 'Assignment marked as complete.');
        }

        // If already inactive, just redirect back with a neutral message or warning
        return Redirect::back()->with('warning', 'Assignment was already inactive.');
    }
}
