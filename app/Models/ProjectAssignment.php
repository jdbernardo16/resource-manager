<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAssignment extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'assignment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'resource_id',
        'assignment_start_date',
        'assignment_end_date',
        'assignment_is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'assignment_start_date' => 'date',
        'assignment_end_date' => 'date',
        'assignment_is_active' => 'boolean',
    ];

    /**
     * Get the project that owns the assignment.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    /**
     * Get the resource that owns the assignment.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id', 'resource_id');
    }
}
