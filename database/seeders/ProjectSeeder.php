<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Resource;
use App\Models\ProjectAssignment;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Use DB for potential transaction

class ProjectSeeder extends Seeder
{
    /**
     * Helper function to calculate end date based on start date and hours.
     */
    private function calculateEndDate(Carbon $startDate, int $hoursEstimate): Carbon
    {
        $endDate = $startDate->copy();
        $hoursPerDay = 7; // As per plan
        $daysNeeded = ceil($hoursEstimate / $hoursPerDay);

        // Adjust if start date itself is a weekend - start counting from next Monday
        if ($startDate->isWeekend()) {
             $startDate = $startDate->next(Carbon::MONDAY);
             $endDate = $startDate->copy(); // Recalculate end date based on adjusted start
        }

        // Add the calculated number of weekdays
        for ($i = 0; $i < $daysNeeded; $i++) {
             if ($endDate->isWeekend()) {
                 $endDate->next(Carbon::MONDAY);
             }
            $endDate->addDay();
            while ($endDate->isWeekend()) {
                $endDate->addDay();
            }
        }
        return $endDate;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch resources needed for assignments
        $john = Resource::where('email', 'john.doe@example.com')->first();
        $jane = Resource::where('email', 'jane.smith@example.com')->first();
        // $peter = Resource::where('email', 'peter.jones@example.com')->first(); // Available
        // $alice = Resource::where('email', 'alice.wonder@example.com')->first(); // Available

        DB::transaction(function () use ($john, $jane) {
            // --- Project 1 (Assigned to John) ---
            $project1StartDate = Carbon::today()->addWeekdays(1); // Start tomorrow (weekday)
            $project1Hours = 80; // Approx 2 weeks +
            $project1 = Project::updateOrCreate(
                ['project_name' => 'Resource Manager App - Phase 1'],
                [
                    'project_name' => 'Resource Manager App - Phase 1',
                    'project_description' => 'Build the core backend functionality.',
                    'start_date' => $project1StartDate,
                    'time_estimate_hours' => $project1Hours,
                    'is_task' => false,
                ]
            );

            if ($john) {
                ProjectAssignment::updateOrCreate(
                    ['project_id' => $project1->project_id, 'resource_id' => $john->resource_id],
                    [
                        'assignment_start_date' => $project1StartDate,
                        'assignment_end_date' => $this->calculateEndDate($project1StartDate, $project1Hours),
                        'assignment_is_active' => true,
                    ]
                );
            }

            // --- Task 1 (Assigned to Jane) ---
            $task1StartDate = Carbon::today()->addWeekdays(3); // Start later this week
            $task1Hours = 15; // Approx 2-3 days
            $task1 = Project::updateOrCreate(
                 ['project_name' => 'Setup Frontend Linting'],
                 [
                    'project_name' => 'Setup Frontend Linting',
                    'project_description' => 'Configure ESLint and Prettier for the Vue frontend.',
                    'start_date' => $task1StartDate,
                    'time_estimate_hours' => $task1Hours,
                    'is_task' => true,
                 ]
            );

            if ($jane) {
                 ProjectAssignment::updateOrCreate(
                    ['project_id' => $task1->project_id, 'resource_id' => $jane->resource_id],
                    [
                        'assignment_start_date' => $task1StartDate,
                        'assignment_end_date' => $this->calculateEndDate($task1StartDate, $task1Hours),
                        'assignment_is_active' => true,
                    ]
                 );
            }

            // --- Project 2 (Unassigned) ---
             $project2StartDate = Carbon::today()->addWeekdays(10); // Start in 2 weeks
             $project2Hours = 120;
             Project::updateOrCreate(
                 ['project_name' => 'Client Website Redesign'],
                 [
                    'project_name' => 'Client Website Redesign',
                    'project_description' => 'Complete overhaul of the main client website.',
                    'start_date' => $project2StartDate,
                    'time_estimate_hours' => $project2Hours,
                    'is_task' => false,
                 ]
             );

        }); // End transaction
    }
}
