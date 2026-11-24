<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['treatment_id', 'name', 'sort_order'])]
class PackageItem extends Model
{
    public $timestamps = false;

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }
}
