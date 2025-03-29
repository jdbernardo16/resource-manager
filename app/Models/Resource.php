<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resource extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'resource_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'skills',
    ];

    /**
     * Get all assignments for the resource.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'resource_id', 'resource_id');
    }

    /**
     * Get the currently active assignment for the resource, if any.
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(ProjectAssignment::class, 'resource_id', 'resource_id')
                    ->where('assignment_is_active', true);
    }

    /**
     * Check if the resource is currently assigned to an active project.
     *
     * @return bool
     */
    public function isCurrentlyAssigned(): bool
    {
        // Check if the activeAssignment relationship exists
        return $this->activeAssignment()->exists();
    }
}
