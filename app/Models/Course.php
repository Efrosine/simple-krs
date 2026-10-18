<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'title',
        'lecturer',
        'credits',
        'day_of_week',
        'starts_at',
        'ends_at',
        'capacity',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function getRemainingSeatsAttribute(): int
    {
        return max(0, $this->capacity - (int) ($this->enrollments_count ?? $this->enrollments()->count()));
    }
}
