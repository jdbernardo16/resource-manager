<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'project_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_name',
        'project_description',
        'start_date',
        'time_estimate_hours',
        'is_task',
        'deadline', // Add deadline here
        'status', // Add status here
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date', // Add deadline cast here
        'is_task' => 'boolean',
    ];

    /**
     * Get the assignments for the project.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'project_id', 'project_id');
    }
}
