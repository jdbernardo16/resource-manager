<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'log_id';

    /**
     * Indicates if the model should be timestamped.
     * We have a dedicated 'timestamp' column.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'details',
        'timestamp', // Include timestamp if you plan to set it manually sometimes
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'details' => 'array', // Cast details to array/object if storing JSON
    ];

    /**
     * Get the user that performed the action (if applicable).
     */
    public function user(): BelongsTo
    {
        // Assuming the default 'id' primary key for the User model
        return $this->belongsTo(User::class, 'user_id');
    }
}
