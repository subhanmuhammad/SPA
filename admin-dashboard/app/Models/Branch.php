<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'address', 'phone', 'opening_time', 'closing_time', 'home_care_max_per_therapist', 'is_active'])]
class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'opening_time' => '09:00:00',
        'closing_time' => '21:00:00',
        'home_care_max_per_therapist' => null,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'home_care_max_per_therapist' => 'integer'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches');
    }

    public function therapists(): HasMany
    {
        return $this->hasMany(Therapist::class);
    }

    public function homecareAreas(): HasMany
    {
        return $this->hasMany(HomecareServiceArea::class)->orderBy('name');
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'branch_treatments')
            ->withPivot('price', 'duration_minutes', 'is_available')
            ->withTimestamps();
    }

    public static function forCustomer(?string $slug): ?self
    {
        return static::active()
            ->when($slug, fn (Builder $q) => $q->where('slug', $slug))
            ->orderBy('id')
            ->first();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
