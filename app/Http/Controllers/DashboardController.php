<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     * Shows available and occupied resources.
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        // Fetch all resources, eager loading their active assignment and the associated project
        $resources = Resource::with(['activeAssignment.project'])
                             ->orderBy('name', 'asc')
                             ->get();

        // Partition resources into available and occupied based on the existence of an active assignment
        [$occupiedResources, $availableResources] = $resources->partition(function ($resource) {
            return $resource->activeAssignment !== null;
        });

        return Inertia::render('Dashboard', [
            'availableResources' => $availableResources->values(), // Reset keys for JS array conversion
            'occupiedResources' => $occupiedResources->values(), // Reset keys for JS array conversion
        ]);
    }
}
