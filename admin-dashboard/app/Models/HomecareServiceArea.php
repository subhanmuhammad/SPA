<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'travel_fee', 'travel_minutes', 'is_active'])]
class HomecareServiceArea extends Model
{
    use HasFactory;

    public const MAX_TRAVEL_MINUTES = 240;

    protected $attributes = ['travel_fee' => 0, 'travel_minutes' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['travel_fee' => 'integer', 'travel_minutes' => 'integer', 'is_active' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
