<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAssignment; // Import ProjectAssignment
use App\Models\Resource; // Import Resource
use Carbon\Carbon; // Import Carbon
use Illuminate\Http\RedirectResponse; // Import RedirectResponse
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB for transactions
use Illuminate\Support\Facades\Redirect; // Import Redirect
use Illuminate\Validation\ValidationException; // Import ValidationException
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type'); // project | task | null
        $status = $request->query('status', 'active'); // Default to 'active' if not provided

        $projectsQuery = Project::query()
            ->orderBy('created_at', 'desc');

        if ($type === 'task') {
            $projectsQuery->where('is_task', true);
        } elseif ($type === 'project') {
            $projectsQuery->where('is_task', false);
        }

        // Apply status filter (only if status is not 'all')
        if ($status && $status !== 'all') {
            $projectsQuery->where('status', $status);
        }

        $projects = $projectsQuery->paginate(15)->withQueryString(); // withQueryString preserves filters

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => [
                'type' => $type,
                'status' => $status, // Pass status filter back to the view
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response // Add Response type hint
    {
        // Fetch resources that do *not* have an active assignment
        $availableResources = Resource::whereDoesntHave('activeAssignment')
                                      ->orderBy('name', 'asc')
                                      ->get(['resource_id', 'name']); // Only fetch needed columns

        return Inertia::render('Projects/Create', [
            'availableResources' => $availableResources,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse // Add RedirectResponse type hint
    {
        $validatedData = $request->validate([
            'project_name' => 'required|string|max:255',
            'project_description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'time_estimate_hours' => 'required|integer|min:1',
            'is_task' => 'sometimes|boolean',
            'deadline' => 'nullable|date|after_or_equal:start_date', // Add deadline validation
            'resource_ids' => 'required|array|min:1', // Expect an array with at least one ID
            'resource_ids.*' => 'required|integer|exists:resources,resource_id', // Each ID must exist
        ]);

        // Removed single resource fetch and availability check here - will check in loop

        // --- End Date Calculation (Considering Multiple Resources) ---
        $startDate = Carbon::parse($validatedData['start_date']);
        $endDate = $startDate->copy();
        $hoursEstimate = $validatedData['time_estimate_hours'];
        $numberOfResources = count($validatedData['resource_ids']); // Get number of assigned resources
        $baseHoursPerDay = 7; // As per plan
        $effectiveHoursPerDay = $baseHoursPerDay * $numberOfResources; // Calculate effective hours
        $daysNeeded = ceil($hoursEstimate / $effectiveHoursPerDay); // Calculate days based on effective hours

        // Adjust if start date itself is a weekend - start counting from next Monday
        if ($startDate->isWeekend()) {
             $startDate = $startDate->next(Carbon::MONDAY);
             $endDate = $startDate->copy(); // Recalculate end date based on adjusted start
        }

        // Add the calculated number of weekdays
        for ($i = 0; $i < $daysNeeded; $i++) {
            // Ensure we only add weekdays
             if ($endDate->isWeekend()) {
                 $endDate->next(Carbon::MONDAY); // Move to Monday if current end date falls on weekend
             }
            $endDate->addDay(); // Add a day first
            // If the added day is a weekend, add more days until it's a weekday
            while ($endDate->isWeekend()) {
                $endDate->addDay();
            }
        }


        DB::beginTransaction();

        try {
            // Create the project
            $project = Project::create([
                'project_name' => $validatedData['project_name'],
                'project_description' => $validatedData['project_description'],
                'start_date' => $startDate, // Use Carbon instance
                'time_estimate_hours' => $validatedData['time_estimate_hours'],
                'is_task' => $validatedData['is_task'] ?? false,
                'deadline' => $validatedData['deadline'] ?? null, // Add deadline
                // Status defaults to 'active' via model or migration, or set explicitly if needed
                // 'status' => 'active',
            ]);

            $resourceErrors = [];
            foreach ($validatedData['resource_ids'] as $resourceId) {
                $resource = Resource::find($resourceId);

                // Check availability for each resource
                if ($resource && $resource->isCurrentlyAssigned()) {
                    // Collect errors instead of throwing immediately to report all conflicts
                    $resourceErrors[$resourceId] = "Resource '{$resource->name}' is already assigned to an active project/task.";
                } else if ($resource) {
                    // Create the assignment if available
                    ProjectAssignment::create([
                        'project_id' => $project->project_id,
                        'resource_id' => $resourceId,
                        'assignment_start_date' => $startDate, // Use Carbon instance
                        'assignment_end_date' => $endDate, // Use calculated end date
                        'assignment_is_active' => true, // Assuming new assignments start active
                    ]);
                }
                // If resource wasn't found, the 'exists' validation already handled it.
            }

            // If any resource was unavailable, rollback and throw validation exception
            if (!empty($resourceErrors)) {
                DB::rollBack();
                // We need to format the errors for the 'resource_ids' field or a general error field
                // For simplicity, let's use a general error message for now, or target the first error
                $firstError = reset($resourceErrors); // Get the first error message
                throw ValidationException::withMessages([
                    // Target the general field or the specific index if possible (more complex)
                    'resource_ids' => 'One or more selected resources are already assigned: ' . $firstError,
                    // Or provide individual errors (requires frontend handling)
                    // 'resource_ids' => $resourceErrors // This might not display well by default
                ]);
            }

            // Optional: Add Activity Log entry here later

            DB::commit();

            return Redirect::route('projects.index')->with('success', 'Project and assignments created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error $e->getMessage()
            // Consider logging the error: Log::error('Project creation failed: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Failed to create project. Please try again.')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        // Eager load assignments and their resources for display
        $project->load(['assignments.resource']);
        return Inertia::render('Projects/Show', ['project' => $project]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
         // Fetch all resources for potential reassignment (or just display current)
         // For simplicity now, just pass the project. Reassignment logic is complex.
         $project->load('assignments.resource'); // Load current assignment if needed

        // Fetch available resources and identify currently assigned ones
        $currentResourceIds = $project->assignments()->pluck('resource_id')->toArray(); // Get array of currently assigned resource IDs
        $projectIdToExclude = $project->project_id; // Get the ID of the project being edited

        // Fetch all potentially relevant resources
        $allResources = Resource::query()
            ->orderBy('name', 'asc')
            ->get(['resource_id', 'name']); // Fetch basic details

        // Enhance resources with assignment status
        $availableResources = $allResources->map(function ($resource) use ($projectIdToExclude, $currentResourceIds) { // Use currentResourceIds here
            // Check if this resource has an active assignment to a DIFFERENT project
            $isAssignedElsewhere = $resource->assignments()
                ->where('assignment_is_active', true) // Assuming active assignments block availability
                ->where('project_id', '!=', $projectIdToExclude)
                ->exists();

            // Add the flag to the resource object/array
            $resource->is_assigned_elsewhere = $isAssignedElsewhere;

            return $resource;
        });

        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'availableResources' => $availableResources,
            'currentResourceIds' => $currentResourceIds, // Pass the array
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
       // Validation includes resource_id now
        $validatedData = $request->validate([
           'project_name' => 'required|string|max:255',
           'project_description' => 'nullable|string',
           'start_date' => 'required|date',
           'time_estimate_hours' => 'required|integer|min:1',
           'is_task' => 'sometimes|boolean',
           'deadline' => 'nullable|date|after_or_equal:start_date', // Add deadline validation
           'resource_ids' => 'required|array|min:1', // Expect an array with at least one ID
           'resource_ids.*' => 'required|integer|exists:resources,resource_id', // Each ID must exist
           'status' => ['required', 'string', \Illuminate\Validation\Rule::in(['active', 'completed', 'archived', 'on_pause'])], // Add status validation
       ]);

       // Get the list of resource IDs submitted in the form
       $newResourceIds = collect($validatedData['resource_ids'])->map(fn($id) => (int)$id)->unique()->toArray();

       DB::beginTransaction();
       try {
           // --- Update Project Details ---
           $project->fill([
               'project_name' => $validatedData['project_name'],
               'project_description' => $validatedData['project_description'],
               'start_date' => Carbon::parse($validatedData['start_date']),
               'time_estimate_hours' => $validatedData['time_estimate_hours'],
               'is_task' => $validatedData['is_task'] ?? $project->is_task,
               'deadline' => $validatedData['deadline'] ?? null, // Add deadline here
               'status' => $validatedData['status'], // Add status here
           ]);
           $project->save(); // Save project details first

           // --- Handle Assignment Updates ---
           $existingResourceIds = $project->assignments()->pluck('resource_id')->toArray();

           $idsToAdd = array_diff($newResourceIds, $existingResourceIds);
           $idsToRemove = array_diff($existingResourceIds, $newResourceIds);

           $assignmentChanged = false; // Flag if any assignment DB operation happens
           $resourceErrors = [];

           // 1. Remove assignments for resources no longer selected
           if (!empty($idsToRemove)) {
               ProjectAssignment::where('project_id', $project->project_id)
                                ->whereIn('resource_id', $idsToRemove)
                                ->delete();
               $assignmentChanged = true;
           }

           // 2. Add assignments for newly selected resources (check availability first)
           foreach ($idsToAdd as $resourceId) {
               $resource = Resource::find($resourceId);
               // Check if the resource is assigned to *another* active project
               if ($resource && $resource->assignments()->where('assignment_is_active', true)->where('project_id', '!=', $project->project_id)->exists()) {
                   $resourceErrors[$resourceId] = "Resource '{$resource->name}' is already assigned to another active project/task.";
               } elseif ($resource) {
                   // Calculate end date for the new assignment (using project's dates and total final resources)
                   $startDate = $project->start_date; // Already a Carbon instance from project fill
                   $endDate = $startDate->copy();
                   $hoursEstimate = $project->time_estimate_hours;
                   $numberOfResources = count($newResourceIds); // Use the count of final intended resources
                   $baseHoursPerDay = 7;
                   $effectiveHoursPerDay = $baseHoursPerDay * ($numberOfResources > 0 ? $numberOfResources : 1); // Avoid division by zero
                   $daysNeeded = ceil($hoursEstimate / $effectiveHoursPerDay);

                   if ($startDate->isWeekend()) {
                       $startDate = $startDate->next(Carbon::MONDAY);
                       $endDate = $startDate->copy();
                   }
                   for ($i = 0; $i < $daysNeeded; $i++) {
                       if ($endDate->isWeekend()) $endDate->next(Carbon::MONDAY);
                       $endDate->addDay();
                       while ($endDate->isWeekend()) $endDate->addDay();
                   }

                   ProjectAssignment::create([
                       'project_id' => $project->project_id,
                       'resource_id' => $resourceId,
                       'assignment_start_date' => $project->start_date,
                       'assignment_end_date' => $endDate,
                       'assignment_is_active' => true, // Assuming new assignments are active
                   ]);
                   $assignmentChanged = true;
               }
           }

           // 3. Handle errors if any resources were unavailable
           if (!empty($resourceErrors)) {
               DB::rollBack();
               $firstError = reset($resourceErrors);
               throw ValidationException::withMessages([
                   'resource_ids' => 'One or more selected resources are already assigned: ' . $firstError,
               ]);
           }

           // 4. Recalculate end dates for *existing* assignments if project dates changed
           // (Only if assignments weren't just added/removed, as those already got calculated)
           if (!$assignmentChanged && ($project->wasChanged('start_date') || $project->wasChanged('time_estimate_hours'))) {
                $assignmentsToUpdate = $project->assignments()->whereIn('resource_id', $newResourceIds)->get(); // Get remaining assignments
                foreach ($assignmentsToUpdate as $assignment) {
                    // Recalculate end date (using final number of resources)
                    $startDate = $project->start_date;
                    $endDate = $startDate->copy();
                    $hoursEstimate = $project->time_estimate_hours;
                    $numberOfResources = count($newResourceIds); // Use the final count
                    $baseHoursPerDay = 7;
                    $effectiveHoursPerDay = $baseHoursPerDay * ($numberOfResources > 0 ? $numberOfResources : 1);
                    $daysNeeded = ceil($hoursEstimate / $effectiveHoursPerDay);

                    if ($startDate->isWeekend()) { $startDate = $startDate->next(Carbon::MONDAY); $endDate = $startDate->copy(); }
                    for ($i = 0; $i < $daysNeeded; $i++) {
                        if ($endDate->isWeekend()) $endDate->next(Carbon::MONDAY);
                        $endDate->addDay();
                        while ($endDate->isWeekend()) $endDate->addDay();
                    }
                    $assignment->assignment_start_date = $project->start_date;
                    $assignment->assignment_end_date = $endDate;
                    $assignment->save();
                }
           }

           DB::commit();
            return Redirect::route('projects.index')->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Project update failed: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Failed to update project. Please try again.')->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        // Deleting a project should probably also delete its assignments.
        // The 'cascadeOnDelete' in the migration handles this if the DB supports it.
        // Otherwise, delete assignments manually within a transaction.
        DB::beginTransaction();
        try {
            // Manually delete assignments if cascade is not reliable/set up
            // $project->assignments()->delete();
            $project->delete();
            DB::commit();
            return Redirect::route('projects.index')->with('success', 'Project deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Project deletion failed: ' . $e->getMessage());
            return Redirect::route('projects.index')->with('error', 'Failed to delete project.');
        }
    }
}
