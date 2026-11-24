<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'components' => 'array',
            'price' => 'integer',
            'normal_price' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }
}
