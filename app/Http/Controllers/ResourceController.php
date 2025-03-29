<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Inertia\Inertia; // Add Inertia import
use Inertia\Response; // Add Response import

class ResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response // Add Response type hint
    {
        $resources = Resource::query()
            ->orderBy('name', 'asc') // Order alphabetically by name
            ->paginate(15); // Paginate results

        return Inertia::render('Resources/Index', [
            'resources' => $resources,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resource $resource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Resource $resource)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        //
    }
}
