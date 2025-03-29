<?php

use App\Http\Controllers\DashboardController; // Import DashboardController
use App\Http\Controllers\ProjectAssignmentController; // Import ProjectAssignmentController
use App\Http\Controllers\ProjectController; // Import ProjectController
use App\Http\Controllers\ResourceController; // Import ResourceController
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Redirect authenticated users to dashboard, otherwise show welcome
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome');
})->name('home');

// Group application routes under auth and verified middleware
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Resource routes for Projects
    Route::resource('projects', ProjectController::class);

    // Resource routes for Resources
    Route::resource('resources', ResourceController::class);

    // Route for completing a project assignment
    Route::post('project-assignments/{assignment}/complete', [ProjectAssignmentController::class, 'complete'])
         ->name('project-assignments.complete');

    // Include settings routes within the authenticated group as well
    require __DIR__.'/settings.php';
});


require __DIR__.'/auth.php'; // Auth routes (login, register, etc.) remain outside the main group
