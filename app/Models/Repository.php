<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the review logs for the repository.
     */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }

    /**
     * Calculates the user's average reviews per day over a given period.
     */
    public function getActualPace(string $period): float
    {
        $startDate = null;

        switch ($period) {
            case '7-days':
                $startDate = Carbon::now()->subDays(7);
                break;
            case '30-days':
                $startDate = Carbon::now()->subDays(30);
                break;
            case 'all-time':
                $firstLog = $this->reviewLogs()->oldest()->first();
                if (!$firstLog) return 0.0;
                $startDate = $firstLog->created_at;
                break;
            default:
                return 0.0;
        }

        $totalReviews = $this->reviewLogs()->where('created_at', '>=', $startDate)->count();

        if ($totalReviews === 0) {
            return 0.0;
        }

        // Calculate days passed, ensuring it's at least 1 to avoid division by zero
        // and to give a meaningful pace even within the first day.
        $days = max(1, $startDate->diffInDays(Carbon::now()));

        return $totalReviews / $days;
    }
}