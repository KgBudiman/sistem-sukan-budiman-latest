<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    protected $fillable = [
        'name',
        'category',
        'max_players_per_group',
        'duration_minutes',
        'group_code',
        'description',
        'rules',
        'equipment',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(SportRegistration::class);
    }

    public function acceptedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'Diterima');
    }

    public function waitingListRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'Senarai Menunggu');
    }

    public function acceptedCount(?int $excludingParticipantId = null): int
    {
        return $this->registrations()
            ->where('status', 'Diterima')
            ->when($excludingParticipantId, fn ($query) => $query->where('participant_id', '!=', $excludingParticipantId))
            ->count();
    }

    public function hasCapacity(?int $excludingParticipantId = null): bool
    {
        if (! $this->max_players_per_group) {
            return true;
        }

        return $this->acceptedCount($excludingParticipantId) < $this->max_players_per_group;
    }

    public function availabilityLabel(): string
    {
        if (! $this->is_active) {
            return 'Tidak Aktif';
        }

        if (! $this->max_players_per_group) {
            return 'Tersedia';
        }

        $accepted = $this->acceptedCount();

        if ($accepted >= $this->max_players_per_group) {
            return 'Senarai Menunggu';
        }

        if ($accepted >= max(1, $this->max_players_per_group - 2)) {
            return 'Hampir Penuh';
        }

        return 'Tersedia';
    }

    public function compatibleWithCategory(string $category): bool
    {
        return $this->category === Participant::CATEGORY_OPEN || $this->category === $category;
    }
}
