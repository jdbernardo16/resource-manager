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
            'resource_id' => 'required|exists:resources,resource_id', // Ensure resource exists
        ]);

        $resource = Resource::find($validatedData['resource_id']);

        // --- Resource Availability Check ---
        if ($resource->isCurrentlyAssigned()) {
            // Throw validation exception to send error back to the form field
            throw ValidationException::withMessages([
                'resource_id' => 'This resource is already assigned to an active project/task.',
            ]);
            // Or redirect back with a general error:
            // return Redirect::back()->withErrors(['resource_id' => 'This resource is already assigned.'])->withInput();
        }

        // --- End Date Calculation (Basic Weekday Logic) ---
        $startDate = Carbon::parse($validatedData['start_date']);
        $endDate = $startDate->copy();
        $hoursEstimate = $validatedData['time_estimate_hours'];
        $hoursPerDay = 7; // As per plan
        $daysNeeded = ceil($hoursEstimate / $hoursPerDay);

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
            ]);

            // Create the assignment
            ProjectAssignment::create([
                'project_id' => $project->project_id,
                'resource_id' => $validatedData['resource_id'],
                'assignment_start_date' => $startDate, // Use Carbon instance
                'assignment_end_date' => $endDate, // Use calculated end date
                'assignment_is_active' => true,
            ]);

            // Optional: Add Activity Log entry here later

            DB::commit();

            return Redirect::route('projects.index')->with('success', 'Project and assignment created successfully.');

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

        // Fetch available resources *plus* the currently assigned one (if any)
        $currentResourceId = $project->assignments()->where('assignment_is_active', true)->first()?->resource_id; // Get ID only if actively assigned
        $projectIdToExclude = $project->project_id; // Get the ID of the project being edited

        // Fetch all potentially relevant resources (e.g., all active ones, adjust if needed)
        $allResources = Resource::query()
            ->orderBy('name', 'asc')
            ->get(['resource_id', 'name']); // Fetch basic details

        // Enhance resources with assignment status
        $availableResources = $allResources->map(function ($resource) use ($projectIdToExclude, $currentResourceId) {
            // Check if this resource has an active assignment to a DIFFERENT project
            $isAssignedElsewhere = $resource->assignments()
                ->where('assignment_is_active', true)
                ->where('project_id', '!=', $projectIdToExclude)
                ->exists();

            // Add the flag to the resource object/array
            $resource->is_assigned_elsewhere = $isAssignedElsewhere;

            return $resource;
        });

        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'availableResources' => $availableResources,
            'currentResourceId' => $currentResourceId,
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
           'resource_id' => 'required|exists:resources,resource_id', // Validate the selected resource ID
           'status' => ['required', 'string', \Illuminate\Validation\Rule::in(['active', 'completed', 'archived', 'on_pause'])], // Add status validation
       ]);

       $newResourceId = $validatedData['resource_id'];
       $assignmentChanged = false; // Flag to track if assignment needs saving

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

           // --- Handle Assignment Update ---
           $assignment = $project->assignments()->first(); // Get the current assignment

           // Check if resource needs to be changed
           if ($assignment && $assignment->resource_id != $newResourceId) {
               // Check availability of the NEW resource (excluding this project)
               $newResource = Resource::find($newResourceId);
               if ($newResource->assignments()->where('assignment_is_active', true)->where('project_id', '!=', $project->project_id)->exists()) {
                   DB::rollBack(); // Rollback before throwing validation exception
                   throw ValidationException::withMessages(['resource_id' => 'The selected resource is already assigned to another active project/task.']);
               }
               // Update resource ID and ensure it's active
               $assignment->resource_id = $newResourceId;
               $assignment->assignment_is_active = true; // Ensure active status
               $assignmentChanged = true;
           } elseif (!$assignment && $newResourceId) {
                // Handle case where project had no assignment but now gets one (edge case?)
                // Check availability of the NEW resource
                $newResource = Resource::find($newResourceId);
                if ($newResource->assignments()->where('assignment_is_active', true)->exists()) { // Check any active assignment
                    DB::rollBack();
                    throw ValidationException::withMessages(['resource_id' => 'The selected resource is already assigned to an active project/task.']);
                }
                // Create a new assignment (needs end date calculation)
                $assignment = new ProjectAssignment([
                    'project_id' => $project->project_id,
                    'resource_id' => $newResourceId,
                    'assignment_is_active' => true, // Assuming it starts active
                ]);
                $assignmentChanged = true; // Will need saving and date calculation
           }


           // Recalculate end date if needed (dates changed OR assignment was created/changed)
           if ($assignment && ($project->wasChanged('start_date') || $project->wasChanged('time_estimate_hours') || $assignmentChanged)) {
                $startDate = $project->start_date; // Already a Carbon instance
                $endDate = $startDate->copy();
                $hoursEstimate = $project->time_estimate_hours;
                $hoursPerDay = 7;
                $daysNeeded = ceil($hoursEstimate / $hoursPerDay);

                if ($startDate->isWeekend()) {
                    $startDate = $startDate->next(Carbon::MONDAY);
                    $endDate = $startDate->copy();
                }

                for ($i = 0; $i < $daysNeeded; $i++) {
                    if ($endDate->isWeekend()) {
                        $endDate->next(Carbon::MONDAY);
                    }
                    $endDate->addDay();
                    while ($endDate->isWeekend()) {
                        $endDate->addDay();
                    }
                }
                $assignment->assignment_start_date = $project->start_date;
                $assignment->assignment_end_date = $endDate;
                $assignmentChanged = true; // Mark as changed if dates were recalculated
           }

           // Save assignment if it was changed or created
           if ($assignment && $assignmentChanged) {
               $assignment->save();
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
