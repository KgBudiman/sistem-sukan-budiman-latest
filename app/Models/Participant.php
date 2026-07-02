<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    public const CATEGORY_CHILD = 'Kanak-Kanak';

    public const CATEGORY_TEENAGER = 'Remaja';

    public const CATEGORY_ADULT = 'Dewasa';

    public const CATEGORY_OPEN = 'Terbuka';

    public const CHILD_AGE_THRESHOLD = 13;

    public const ADULT_AGE_THRESHOLD = 18;

    public const PARTICIPANT_CATEGORIES = [
        self::CATEGORY_CHILD,
        self::CATEGORY_TEENAGER,
        self::CATEGORY_ADULT,
    ];

    public const SPORT_CATEGORIES = [
        self::CATEGORY_CHILD,
        self::CATEGORY_TEENAGER,
        self::CATEGORY_ADULT,
        self::CATEGORY_OPEN,
    ];

    protected $fillable = [
        'registration_code',
        'name',
        'age',
        'phone',
        'category',
        'house_id',
        'guardian_id',
        'status',
        'notes',
    ];

    public static function categoryForAge(int $age): string
    {
        if ($age < self::CHILD_AGE_THRESHOLD) {
            return self::CATEGORY_CHILD;
        }

        if ($age < self::ADULT_AGE_THRESHOLD) {
            return self::CATEGORY_TEENAGER;
        }

        return self::CATEGORY_ADULT;
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function sportRegistrations(): HasMany
    {
        return $this->hasMany(SportRegistration::class);
    }

    public function getIsChildAttribute(): bool
    {
        return self::categoryForAge((int) $this->age) === self::CATEGORY_CHILD;
    }
}
