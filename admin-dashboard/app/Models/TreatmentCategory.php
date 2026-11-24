<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'tagline', 'description', 'sort_order'])]
class TreatmentCategory extends Model
{
    public const PROMO = 'paket-promo-zenova';

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }
}
