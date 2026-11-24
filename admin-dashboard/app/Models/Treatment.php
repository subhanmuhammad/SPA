<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'treatment_category_id', 'name', 'description', 'service_type', 'duration_minutes',
    'price', 'normal_price', 'promo_starts_on', 'promo_ends_on', 'is_active', 'sort_order',
])]
class Treatment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'normal_price' => 'integer',
            'promo_starts_on' => 'date',
            'promo_ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Treatment $treatment) {
            $base = Str::slug($treatment->name);
            $slug = $base;
            for ($i = 2; static::withTrashed()->where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $treatment->slug = $slug;
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TreatmentCategory::class, 'treatment_category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class, 'package_id')->orderBy('sort_order');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_treatments')
            ->withPivot('price', 'duration_minutes', 'is_available')
            ->withTimestamps();
    }

    public function scopeOfferedAt(Builder $query, Branch $branch): void
    {
        $today = now()->toDateString();

        $query->join('branch_treatments', function ($join) use ($branch) {
            $join->on('branch_treatments.treatment_id', '=', 'treatments.id')
                ->where('branch_treatments.branch_id', $branch->id)
                ->where('branch_treatments.is_available', true);
        })
            ->where('treatments.is_active', true)
            ->where(fn ($q) => $q->whereNull('promo_starts_on')->orWhere('promo_starts_on', '<=', $today))
            ->where(fn ($q) => $q->whereNull('promo_ends_on')->orWhere('promo_ends_on', '>=', $today))
            ->select([
                'treatments.id', 'treatments.treatment_category_id', 'treatments.name', 'treatments.slug',
                'treatments.description', 'treatments.service_type', 'treatments.normal_price',
                'treatments.promo_starts_on', 'treatments.promo_ends_on', 'treatments.photo_path',
            ])
            ->selectRaw('COALESCE(branch_treatments.price, treatments.price) as price')
            ->selectRaw('COALESCE(branch_treatments.duration_minutes, treatments.duration_minutes) as duration_minutes')
            ->orderBy('treatments.sort_order')
            ->orderBy('treatments.id');
    }

    public function requiredSkillIds(): array
    {
        $linked = $this->items->pluck('treatment_id')->filter()->unique()->values()->all();

        return $linked ?: [$this->id];
    }

    public function savings(): int
    {
        return $this->normal_price ? max(0, $this->normal_price - $this->price) : 0;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
