<?php

namespace App\Observers;

use App\Models\ActivityLog; // Import ActivityLog
use App\Models\Resource;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class ResourceObserver
{
    /**
     * Handle the Resource "created" event.
     */
    public function created(Resource $resource): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'RESOURCE_CREATED',
            'details' => json_encode([
                'resource_id' => $resource->resource_id,
                'resource_name' => $resource->name,
                'resource_email' => $resource->email,
            ]),
        ]);
    }

    /**
     * Handle the Resource "updated" event.
     */
    public function updated(Resource $resource): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'RESOURCE_UPDATED',
            'details' => json_encode([
                'resource_id' => $resource->resource_id,
                'resource_name' => $resource->name,
                'changes' => $resource->getChanges(),
            ]),
        ]);
    }

    /**
     * Handle the Resource "deleted" event.
     */
    public function deleted(Resource $resource): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'RESOURCE_DELETED',
            'details' => json_encode([
                'resource_id' => $resource->resource_id,
                'resource_name' => $resource->name,
            ]),
        ]);
    }

    /**
     * Handle the Resource "restored" event.
     */
    public function restored(Resource $resource): void
    {
        // Optional: Log restoration if using soft deletes
    }

    /**
     * Handle the Resource "force deleted" event.
     */
    public function forceDeleted(Resource $resource): void
    {
        // Optional: Log force deletion if using soft deletes
    }
}
