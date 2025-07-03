<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLog extends Model
{
    /**
     * Indicates if the model should be timestamped. `created_at` is used, but `updated_at` is disabled.
     *
     * @var bool
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'repository_id',
        'commit_sha',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the repository that this review log belongs to.
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}